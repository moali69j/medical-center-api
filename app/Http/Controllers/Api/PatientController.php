<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * جلب قائمة أرشيف كافة المرضى المسجلين بالمركز مع عداد زياراتهم.
     * Fixed: uses withCount() instead of map()+count() to avoid N+1 queries.
     * Paginated to prevent loading thousands of records at once.
     */
   public function index()
{
    $patients = Patient::withCount('caseReports')
        ->latest()
        ->paginate(15);


    return response()->json($patients); // أو response()->json($patients)
}

    public function search(Request $request)
{
    $query = $request->input('query');

    if (empty($query)) {
        return response()->json(Patient::withCount('caseReports')->latest()->paginate(15));
    }

    $patients = Patient::withCount('caseReports')
        ->where('full_name', 'LIKE', "%{$query}%")
        ->orWhere('phone', 'LIKE', "%{$query}%")
        ->orWhere('national_id', 'LIKE', "%{$query}%")
        ->paginate(15);

    return response()->json($patients);
}
}