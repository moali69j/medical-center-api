<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    // جلب قائمة أرشيف كافة المرضى المسجلين بالمركز مع عداد زياراتهم
public function index()
{
    $patients = Patient::latest()->get()->map(function($patient) {
        // حساب عدد الحالات/الزيارات الإجمالية لكل مريض تلقائياً ليعرف مدير المركز ولائه للمركز
        $patient->cases_count = $patient->caseReports()->count();
        return $patient;
    });

    return response()->json($patients);
}
   public function search(Request $request)
{
    $query = $request->get('query');

    if (empty($query)) {
        return response()->json([]);
    }

    // جلب المرضى مع التأكيد على جلب كافة الحقول
    $patients = Patient::where('full_name', 'LIKE', "%{$query}%")
        ->orWhere('phone', 'LIKE', "%{$query}%")
        ->orWhere('national_id', 'LIKE', "%{$query}%")
        ->with(['caseReports' => function($q) {
            $q->latest()->with('services');
        }])
        ->get()
        ->map(function($patient) {
            $patient->cases_count = $patient->caseReports->count();
            return $patient;
        });

    return response()->json($patients);
}
}