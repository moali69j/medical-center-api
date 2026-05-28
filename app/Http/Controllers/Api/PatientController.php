<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
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