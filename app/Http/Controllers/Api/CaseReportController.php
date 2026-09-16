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
        // 1. التحقق من البيانات مع التحقق من حقل العمر
        $request->validate([
            'patient.id' => 'nullable|exists:patients,id',
            'patient.full_name' => 'required|string|max:255',
            'patient.phone' => 'required|string',
            'patient.national_id' => 'nullable|string',
            'patient.age' => 'nullable|integer|min:0|max:120',
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
            'extra_items.*.id' => 'required|exists:inventory_items,id,deleted_at,NULL',
        ]);

        return DB::transaction(function () use ($request) {
            
            // 2. معالجة بيانات المريض التراكمية (إنشاء أو تحديث)
            $patientData = $request->input('patient');
            
            $patientAttributes = [
                'full_name' => $patientData['full_name'],
                'phone' => $patientData['phone'],
                'national_id' => $patientData['national_id'] ?? null,
                'age' => $patientData['age'] ?? null,
                'address' => $patientData['address'] ?? null,
                'blood_type' => $patientData['blood_type'] ?? null,
                'chronic_diseases' => $patientData['chronic_diseases'] ?? null,
                'current_medications' => $patientData['current_medications'] ?? null,
                'permanent_medical_notes' => $patientData['permanent_medical_notes'] ?? null,
            ];

            if (isset($patientData['id']) && $patientData['id']) {
                $patient = Patient::findOrFail($patientData['id']);
                $patient->update($patientAttributes);
            } else {
                $patient = Patient::create($patientAttributes);
            }


            // 3. جلب الإعدادات المالية الحالية
            $creditPrice = (float) Setting::get('credit_price', 1000);
            $caseData = $request->input('case');
            
            // 4. حساب تكاليف المواد المستهلكة
            $totalCostOfMaterials = 0;
            $itemsToSubtract = []; 

            $services = Service::withTrashed()->with(['materials' => function($q) {
                $q->withTrashed();
            }])->whereIn('id', $request->services)->get();

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

            if ($request->has('extra_items') && is_array($request->extra_items)) {
                foreach ($request->extra_items as $extraItem) {
                    $item = InventoryItem::withTrashed()->find($extraItem['id']);
                    if (!$item || !$item->is_measurable) {
                        continue; // حماية: منع احتساب أو خصم مواد العهدة العامة غير القابلة للقياس مع الزيارات الفردية
                    }
                    $qtyNeeded = (float) $extraItem['quantity'];
                    $totalCostOfMaterials += ($item->cost_price * $qtyNeeded);
                    
                    if (!isset($itemsToSubtract[$item->id])) {
                        $itemsToSubtract[$item->id] = 0;
                    }
                    $itemsToSubtract[$item->id] += $qtyNeeded;
                }
            }

            foreach ($itemsToSubtract as $itemId => $totalQty) {
                $item = InventoryItem::withTrashed()->lockForUpdate()->find($itemId);
                
                if ($item->trashed()) {
                    return response()->json([
                        'message' => "المادة ({$item->name}) مؤرشفة ومحذوفة ناعماً، لا يمكن استخدامها في زيارة جديدة!"
                    ], 422);
                }

                if ($item->quantity < $totalQty) {
                    return response()->json([
                        'message' => "المادة ({$item->name}) غير كافية بالمستودع! المتوفر: {$item->quantity} والمطلوب: {$totalQty}"
                    ], 422);
                }
            }

            foreach ($itemsToSubtract as $itemId => $totalQty) {
                $item = InventoryItem::lockForUpdate()->find($itemId);
                if ($item) {
                    $item->decrement('quantity', $totalQty);
                }
            }

            // 5. احتساب الحصص والأرباح المالية الصافية
            $totalPaid = (float) $caseData['total_paid'];
            $netProfit = $totalPaid - $totalCostOfMaterials; 

            if ($caseData['case_type'] === 'internal') {
                $centerShare = $netProfit * 0.60; 
                $staffShare = $netProfit * 0.40;  
            } else {
                $centerShare = $netProfit * 0.40; 
                $staffShare = $netProfit * 0.60;  
            }

            // 6. إنشاء سجل الحالة
            $caseReport = CaseReport::create([
                'patient_id' => $patient->id,
                'case_type' => $caseData['case_type'],
                'blood_pressure' => $caseData['blood_pressure'] ?? null,
                'sugar_level' => $caseData['sugar_level'] ?? null,
                'oxygen_saturation' => $caseData['oxygen_saturation'] ?? null,
                'credit_price_at_time' => $creditPrice,
                'total_paid' => $totalPaid,
                'total_cost_of_materials' => $totalCostOfMaterials,
                'center_share' => max(0, $centerShare), 
                'staff_share' => max(0, $staffShare),
                'visit_notes' => $caseData['visit_notes'] ?? null,
            ]);

            $caseReport->services()->attach($request->services);

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