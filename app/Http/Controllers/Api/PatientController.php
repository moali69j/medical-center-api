<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    // دالة البحث عن مريض بالهاتف أو الاسم أو الرقم الوطني
    public function search(Request $request)
    {
        $query = $request->query('query');

        $patient = Patient::where('phone', $query)
            ->orWhere('national_id', $query)
            ->orWhere('full_name', 'LIKE', "%{$query}%")
            ->first();

        if ($patient) {
            return response()->json($patient);
        }

        return response()->json(null, 404);
    }
}