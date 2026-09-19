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

        // 2. بناء استعلام الحالات كاستعلام SQL مجمّع عالي الكفاءة والدقة
        $caseQuery = DB::table('case_reports');

        if (!empty($fromDate)) {
            $caseQuery->whereDate('case_reports.created_at', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $caseQuery->whereDate('case_reports.created_at', '<=', $toDate);
        }
        if (!empty($serviceId)) {
            $caseQuery->whereExists(function ($query) use ($serviceId) {
                $query->select(DB::raw(1))
                      ->from('case_report_service')
                      ->whereColumn('case_report_service.case_report_id', 'case_reports.id')
                      ->where('case_report_service.service_id', $serviceId);
            });
        }

        $caseAggregates = (clone $caseQuery)
            ->selectRaw('
                COUNT(*) as total_cases,
                COALESCE(SUM(total_paid), 0) as total_revenue,
                COALESCE(SUM(total_cost_of_materials), 0) as total_cost_of_materials,
                COALESCE(SUM(center_share), 0) as total_center_share,
                COALESCE(SUM(staff_share), 0) as total_staff_share
            ')->first();

        // حساب إجمالي النقاط (Credits) المستهلكة في الفترة مباشرة في SQL
        $creditsQuery = DB::table('case_report_service')
            ->join('services', 'case_report_service.service_id', '=', 'services.id')
            ->join('case_reports', 'case_report_service.case_report_id', '=', 'case_reports.id');

        if (!empty($fromDate)) {
            $creditsQuery->whereDate('case_reports.created_at', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $creditsQuery->whereDate('case_reports.created_at', '<=', $toDate);
        }
        if (!empty($serviceId)) {
            $creditsQuery->where('case_report_service.service_id', $serviceId);
        }
        $totalCreditsConsumed = (int) $creditsQuery->sum('services.credits_required');

        // 3. بناء استعلام المصاريف والإيداعات بنفس النطاق الزمني مباشرة في SQL
        $baseExpenseQuery = DB::table('expenses');
        if (!empty($fromDate)) {
            $baseExpenseQuery->whereDate('created_at', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $baseExpenseQuery->whereDate('created_at', '<=', $toDate);
        }

        // المصاريف والمسحوبات الفعلية (Outflows)
        $totalExpensesAmount = (float) (clone $baseExpenseQuery)
            ->where(function($q) {
                $q->where('transaction_type', '!=', 'deposit')
                  ->where('category', 'not like', '%إيداع%')
                  ->where('category', 'not like', '%تغذية%');
            })->sum('amount');

        // إيداعات وتغذية الصندوق النقدية (Inflows)
        $totalDepositsAmount = (float) (clone $baseExpenseQuery)
            ->where(function($q) {
                $q->where('transaction_type', 'deposit')
                  ->orWhere('category', 'like', '%إيداع%')
                  ->orWhere('category', 'like', '%تغذية%');
            })->sum('amount');

        // 4. كاش الصندوق الفعلي المتوفر حالياً بالمركز (الرصيد التراكمي الشامل لكافة الأوقات)
        $allTimeRevenue = (float) DB::table('case_reports')->sum('total_paid');
        $allTimeExpenses = (float) DB::table('expenses')
            ->where(function($q) {
                $q->where('transaction_type', '!=', 'deposit')
                  ->where('category', 'not like', '%إيداع%')
                  ->where('category', 'not like', '%تغذية%');
            })->sum('amount');

        $allTimeDeposits = (float) DB::table('expenses')
            ->where(function($q) {
                $q->where('transaction_type', 'deposit')
                  ->orWhere('category', 'like', '%إيداع%')
                  ->orWhere('category', 'like', '%تغذية%');
            })->sum('amount');

        $currentCashInTreasury = $allTimeRevenue + $allTimeDeposits - $allTimeExpenses;

        $totalRevenue = (float) ($caseAggregates->total_revenue ?? 0);
        $periodNetCash = $totalRevenue + $totalDepositsAmount - $totalExpensesAmount;

        // 5. إحصائيات الخدمات الأكثر استخداماً وطلباً بالفترة المحددة
        $serviceStatsQuery = DB::table('case_report_service')
            ->join('services', 'case_report_service.service_id', '=', 'services.id')
            ->join('case_reports', 'case_report_service.case_report_id', '=', 'case_reports.id')
            ->select('services.name', DB::raw('count(case_report_service.id) as usage_count'));

        if (!empty($fromDate)) {
            $serviceStatsQuery->whereDate('case_reports.created_at', '>=', $fromDate);
        }
        if (!empty($toDate)) {
            $serviceStatsQuery->whereDate('case_reports.created_at', '<=', $toDate);
        }
        if (!empty($serviceId)) {
            $serviceStatsQuery->where('case_report_service.service_id', $serviceId);
        }

        $serviceStats = $serviceStatsQuery
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('usage_count')
            ->get();

        // 6. إرجاع الملخص المالي السريع
        return response()->json([
            'summary' => [
                'total_cases' => (int) ($caseAggregates->total_cases ?? 0),
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpensesAmount,
                'total_deposits' => $totalDepositsAmount,
                'current_cash_in_treasury' => $currentCashInTreasury,
                'period_net_cash' => $periodNetCash,
                'total_cost_of_materials' => (float) ($caseAggregates->total_cost_of_materials ?? 0),
                'total_center_share' => (float) ($caseAggregates->total_center_share ?? 0),
                'total_staff_share' => (float) ($caseAggregates->total_staff_share ?? 0),
                'total_credits_consumed' => $totalCreditsConsumed,
            ],
            'service_analytics' => $serviceStats
        ]);
    }

    /**
     * مسار مخصص وسريع لجلب سجل الحالات والزيارات مع الترقيم والبحث المتقدم
     */
    public function getCasesLog(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');
        $caseType = $request->query('case_type');
        $search = $request->query('search');

        $query = CaseReport::with(['patient', 'services' => function($q) {
            $q->withTrashed();
        }]);

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }
        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }
        if ($caseType) {
            $query->where('case_type', $caseType);
        }
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhereHas('patient', function($pq) use ($search) {
                      $pq->where('full_name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('national_id', 'like', "%{$search}%");
                  });
            });
        }

        $paginated = $query->latest()->paginate(25);

        return response()->json($paginated);
    }

    public function getStaffReport(Request $request)
    {
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        // بناء الاستعلام لحصص الكادر (للحالات التي لم يتم تصفيتها بعد فقط)
        $caseQuery = CaseReport::with('patient')->where('is_paid_to_staff', false);

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
                'date' => $case->created_at->format('Y-m-d'),
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

    /**
     * جلب رصيد كاش الصندوق الفعلي في المركز لحظياً
     */
    public function getTreasuryBalance()
    {
        $allTimeRevenue = (float) DB::table('case_reports')->sum('total_paid');
        $allTimeExpenses = (float) DB::table('expenses')
            ->where(function($q) {
                $q->where('transaction_type', '!=', 'deposit')
                  ->where('category', 'not like', '%إيداع%')
                  ->where('category', 'not like', '%تغذية%');
            })->sum('amount');

        $allTimeDeposits = (float) DB::table('expenses')
            ->where(function($q) {
                $q->where('transaction_type', 'deposit')
                  ->orWhere('category', 'like', '%إيداع%')
                  ->orWhere('category', 'like', '%تغذية%');
            })->sum('amount');

        $currentCash = $allTimeRevenue + $allTimeDeposits - $allTimeExpenses;

        return response()->json([
            'current_cash_in_treasury' => $currentCash,
            'all_time_revenue' => $allTimeRevenue,
            'all_time_deposits' => $allTimeDeposits,
            'all_time_expenses' => $allTimeExpenses,
        ]);
    }
}