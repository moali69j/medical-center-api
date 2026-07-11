<?php

namespace App\Http\Controllers\Api;

use App\Exports\CaseReportsExport;
use App\Exports\ExpensesExport;
use App\Exports\InventoryExport;
use App\Exports\PatientsExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * تصدير قائمة المرضى إلى ملف Excel.
     * GET /api/export/patients
     */
    public function patients()
    {
        $filename = 'patients-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new PatientsExport(), $filename);
    }

    /**
     * تصدير سجلات الحالات إلى ملف Excel مع دعم الفلترة بالتاريخ.
     * GET /api/export/cases?from_date=2026-01-01&to_date=2026-12-31
     */
    public function cases(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
        ]);

        $filename = 'case-reports-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new CaseReportsExport($request->from_date, $request->to_date),
            $filename
        );
    }

    /**
     * تصدير سجلات المصاريف التشغيلية إلى ملف Excel مع دعم الفلترة بالتاريخ.
     * GET /api/export/expenses?from_date=2026-01-01&to_date=2026-12-31
     */
    public function expenses(Request $request)
    {
        $request->validate([
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
        ]);

        $filename = 'expenses-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(
            new ExpensesExport($request->from_date, $request->to_date),
            $filename
        );
    }

    /**
     * تصدير قائمة المستودع (المواد والأصناف) إلى ملف Excel.
     * GET /api/export/inventory
     */
    public function inventory()
    {
        $filename = 'inventory-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new InventoryExport(), $filename);
    }
}
