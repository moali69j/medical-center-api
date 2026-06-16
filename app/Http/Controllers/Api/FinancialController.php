<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseReport;
use App\Models\Expense;
use App\Models\Service;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialController extends Controller
{
    public function getReport(Request $request)
    {
        // 1. استقبال فلاتر البحث والزمن
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $serviceId = $request->query('service_id');

        // 2. بناء استعلام الحالات (Case Reports Query)
        $caseQuery = CaseReport::with(['services' => function($q) {
            $q->withTrashed(); // حتى لو حذفت الخدمة ناعماً تظهر في الحسابات التاريخية
        }]);

        // تطبيق الفلترة الزمنية إن وجدت
        if ($fromDate) {
            $caseQuery->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $caseQuery->whereDate('created_at', '<=', $toDate);
        }

        // فلترة الحالات بناءً على خدمة معينة تم استخدامها
        if ($serviceId) {
            $caseQuery->whereHas('services', function($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            });
        }

        $cases = $caseQuery->latest()->get();

        // 3. بناء استعلام المصاريف بنفس النطاق الزمني
        $expenseQuery = Expense::query();
        if ($fromDate) $expenseQuery->whereDate('created_at', '>=', $fromDate);
        if ($toDate) $expenseQuery->whereDate('created_at', '<=', $toDate);
        
        $expenses = $expenseQuery->latest()->get();

        // 4. الحسابات المالية والكريدت الذكي التراكمي
        $totalRevenue = 0;             // إجمالي الكاش المستلم فعلياً
        $totalCostOfMaterials = 0;     // إجمالي تكاليف المستهلكات
        $totalCenterShare = 0;         // حصة المركز الصافية من العمليات
        $totalStaffShare = 0;          // حصة الكادر الطبي التراكمية
        $totalCreditsConsumed = 0;     // مجموع النقاط المستهلكة

        foreach ($cases as $case) {
            $totalRevenue += (float) $case->total_paid;
            $totalCostOfMaterials += (float) $case->total_cost_of_materials;
            $totalCenterShare += (float) $case->center_share;
            $totalStaffShare += (float) $case->staff_share;

            // حساب النقاط للخدمات في هذه الحالة
            foreach ($case->services as $service) {
                $totalCreditsConsumed += (int) $service->credits_required;
            }
        }

        $totalExpensesAmount = $expenses->sum('amount');
        
        // المعادلة الذهبية: صافي أرباح صاحب المركز الحقيقية بعد خصم المصاريف التشغيلية
        $finalNetProfitForCenter = $totalCenterShare - $totalExpensesAmount;

        // 5. إحصائيات الخدمات الأكثر استخداماً وطلباً (للرسم البياني)
        // نقوم بجلب التكرار عبر جدول الـ Pivot الوسيط
        $serviceStats = DB::table('case_report_service')
            ->join('services', 'case_report_service.service_id', '=', 'services.id')
            ->select('services.name', DB::raw('count(case_report_service.id) as usage_count'))
            ->when($fromDate, function($q) use ($fromDate) {
                return $q->whereDate('case_report_service.created_at', '>=', $fromDate);
            })
            ->when($toDate, function($q) use ($toDate) {
                return $q->whereDate('case_report_service.created_at', '<=', $toDate);
            })
            ->groupBy('services.id', 'services.name')
            ->orderBy('usage_count', 'desc')
            ->get();

        // 6. إرجاع البيانات المنظمة للفرونت إند لطباعة الإكسل والتحليل
        return response()->json([
            'summary' => [
                'total_cases' => $cases->count(),
                'total_revenue' => $totalRevenue,
                'total_cost_of_materials' => $totalCostOfMaterials,
                'total_center_share' => $totalCenterShare,
                'total_staff_share' => $totalStaffShare,
                'total_expenses' => $totalExpensesAmount,
                'final_net_profit' => $finalNetProfitForCenter,
                'total_credits_consumed' => $totalCreditsConsumed,
            ],
            'cases_details' => $cases,
            'expenses_details' => $expenses,
            'service_analytics' => $serviceStats
        ]);
    }
public function getStaffReport(Request $request)
{
    $fromDate = $request->query('from_date');
    $toDate = $request->query('to_date');

    // بناء الاستعلام لحصص الكادر
    $caseQuery = CaseReport::with('patient');

    // حماية: نطبق الفلترة الزمنية فقط إذا قام المستخدم باختيار تاريخ فعلي من الواجهة
    if (!empty($fromDate)) {
        $caseQuery->whereDate('created_at', '>=', $fromDate);
    }
    if (!empty($toDate)) {
        $caseQuery->whereDate('created_at', '<=', $toDate);
    }

    $cases = $caseQuery->latest()->get()->map(function($case) {
        return [
            'id' => $case->id,
            'patient_name' => $case->patient->full_name ?? 'مريض غير معرف',
            'date' => $case->created_at->format('Y-m-d'), // تنسيق التاريخ القياسي
            'case_type' => $case->case_type,
            'total_paid' => (float) $case->total_paid,
            'staff_share' => (float) $case->staff_share,
        ];
    });

    $totalStaffOwed = $cases->sum('staff_share');

    return response()->json([
        'total_staff_owed' => $totalStaffOwed,
        'detailed_shares' => $cases
    ]);
}
}