<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseReport;
use App\Models\Patient;
use App\Models\InventoryItem;
use App\Models\Setting;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaseReportController extends Controller
{
    public function store(Request $request)
    {
        // 1. التحقق من البيانات بناءً على الهيكل الجديد (patient و case)
        $request->validate([
            'patient.full_name' => 'required|string|max:255',
            'patient.phone' => 'required|string',
            'patient.national_id' => 'nullable|string',
            'patient.address' => 'nullable|string',
            'patient.blood_type' => 'nullable|string',
            'patient.chronic_diseases' => 'nullable|string',
            'patient.current_medications' => 'nullable|string',
            'patient.permanent_medical_notes' => 'nullable|string',
            
            'case.case_type' => 'required|in:internal,external',
            'case.blood_pressure' => 'nullable|string',
            'case.sugar_level' => 'nullable|string',
            'case.oxygen_saturation' => 'nullable|string',
            'case.visit_notes' => 'nullable|string',
            'case.total_paid' => 'required|numeric|min:0',
            
            'services' => 'required|array|min:1',
            'extra_items' => 'nullable|array',
            'extra_items.*.id' => 'required|exists:inventory_items,id',
            'extra_items.*.quantity' => 'required|numeric|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            
            // 2. معالجة بيانات المريض التراكمية (إنشاء أو تحديث)
            $patientData = $request->input('patient');
            
            $patient = Patient::updateOrCreate(
                // نبحث عنه بالهاتف أو الرقم الوطني إذا كان قديماً
                ['phone' => $patientData['phone']],
                [
                    'full_name' => $patientData['full_name'],
                    'national_id' => $patientData['national_id'] ?? null,
                    'address' => $patientData['address'] ?? null,
                    'blood_type' => $patientData['blood_type'] ?? null,
                    'chronic_diseases' => $patientData['chronic_diseases'] ?? null,
                    'current_medications' => $patientData['current_medications'] ?? null,
                    'permanent_medical_notes' => $patientData['permanent_medical_notes'] ?? null,
                ]
            );

            // 3. جلب الإعدادات المالية الحالية
            $creditPrice = (float) Setting::get('credit_price', 1000);
            $caseData = $request->input('case');
            
            // 4. حساب تكاليف المواد المستهلكة (التلقائية والإضافية) لتأمين النظام المالي
            $totalCostOfMaterials = 0;
            $itemsToSubtract = []; // مصفوفة مؤقتة لتخزين الكميات المراد خصمها بعد الحساب

            // أ) حساب وتجهيز خصم المواد التلقائية التابعة للخدمات المختارة
            $services = Service::with('materials')->whereIn('id', $request->services)->get();
            foreach ($services as $service) {
                foreach ($service->materials as $material) {
                    $qtyNeeded = (float) $material->pivot->quantity;
                    $totalCostOfMaterials += ($material->cost_price * $qtyNeeded);
                    
                    if (!isset($itemsToSubtract[$material->id])) {
                        $itemsToSubtract[$material->id] = 0;
                    }
                    $itemsToSubtract[$material->id] += $qtyNeeded;
                }
            }

            // ب) حساب وتجهيز خصم المواد الإضافية الكاش (خارج الخدمة)
            if ($request->has('extra_items') && is_array($request->extra_items)) {
                foreach ($request->extra_items as $extraItem) {
                    $item = InventoryItem::find($extraItem['id']);
                    $qtyNeeded = (float) $extraItem['quantity'];
                    $totalCostOfMaterials += ($item->cost_price * $qtyNeeded);
                    
                    if (!isset($itemsToSubtract[$item->id])) {
                        $itemsToSubtract[$item->id] = 0;
                    }
                    $itemsToSubtract[$item->id] += $qtyNeeded;
                }
            }

            // ج) التحقق من توفر الكميات في المستودع قبل الخصم الفعلي منعاً للوقوع تحت الصفر
            foreach ($itemsToSubtract as $itemId => $totalQty) {
                $item = InventoryItem::find($itemId);
                if ($item->quantity < $totalQty) {
                    // إلغاء العملية بأكملها وإعادة الخطأ للممرض
                    return response()->json([
                        'message' => "المادة ({$item->name}) غير كافية بالمستودع! المتوفر: {$item->quantity} والمطلوب: {$totalQty}"
                    ], 422);
                }
            }

            // د) الخصم الفعلي المستقر من المخزن الآن
            foreach ($itemsToSubtract as $itemId => $totalQty) {
                $item = InventoryItem::find($itemId);
                $item->decrement('quantity', $totalQty);
            }

            // 5. احتساب الحصص والأرباح المالية الصافية للمركز والكادر
            $totalPaid = (float) $caseData['total_paid'];
            $netProfit = $totalPaid - $totalCostOfMaterials; // الصافي بعد التكلفة

            if ($caseData['case_type'] === 'internal') {
                $centerShare = $netProfit * 0.60; // 60% للمركز
                $staffShare = $netProfit * 0.40;  // 40% للكادر
            } else {
                $centerShare = $netProfit * 0.40; // 40% للمركز
                $staffShare = $netProfit * 0.60;  // 60% للكادر
            }

            // 6. إنشاء سجل الحالة (الزيارة الحالية) وحفظ الأرقام المالية النهائية وثبيتها
            $caseReport = CaseReport::create([
                'patient_id' => $patient->id,
                'case_type' => $caseData['case_type'],
                'blood_pressure' => $caseData['blood_pressure'] ?? null,
                'sugar_level' => $caseData['sugar_level'] ?? null,
                'oxygen_saturation' => $caseData['oxygen_saturation'] ?? null,
                'credit_price_at_time' => $creditPrice,
                'total_paid' => $totalPaid,
                'total_cost_of_materials' => $totalCostOfMaterials,
                'center_share' => max(0, $centerShare), // نضمن ألا تكون الحصص بالسالب
                'staff_share' => max(0, $staffShare),
                'visit_notes' => $caseData['visit_notes'] ?? null,
            ]);

            // 7. ربط الخدمات بالحالة الحالية في جدول الـ Pivot
            $caseReport->services()->attach($request->services);

            // 8. ربط المواد الإضافية بالحالة الحالية في جدول الـ Pivot (إن وجدت)
            if ($request->has('extra_items') && is_array($request->extra_items)) {
                foreach ($request->extra_items as $extraItem) {
                    $caseReport->items()->attach($extraItem['id'], [
                        'used_quantity' => $extraItem['quantity']
                    ]);
                }
            }

            return response()->json([
                'message' => 'تم تسجيل الحالة بنجاح، وتوزيع الحصص المالية وخصم مستودع المواد!',
                'case_id' => $caseReport->id
            ], 201);
        });
    }
}