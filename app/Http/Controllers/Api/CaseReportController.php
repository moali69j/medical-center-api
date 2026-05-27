<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\CaseReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CaseReportController extends Controller
{
    public function store(Request $request)
    {
        // 1. التحقق من البيانات القادمة من React
        $validated = $request->validate([
            'phone' => 'required',
            'full_name' => 'required',
            'case_type' => 'required|in:internal,external',
            'services' => 'required|array', // مصفوفة من ID الخدمات
            'total_paid' => 'required|numeric',
        ]);

        return DB::transaction(function () use ($request) {
            // 2. معالجة المريض (Update if exists, Create if not)
            $patient = Patient::updateOrCreate(
                ['phone' => $request->phone], // البحث عن طريق الهاتف
                [
                    'full_name' => $request->full_name,
                    'national_id' => $request->national_id,
                    'address' => $request->address,
                    'blood_type' => $request->blood_type,
                    'chronic_diseases' => $request->chronic_diseases,
                    'current_medications' => $request->current_medications,
                ]
            );

            // 3. إنشاء تقرير الحالة
            $case = CaseReport::create([
                'patient_id' => $patient->id,
                'case_type' => $request->case_type,
                'blood_pressure' => $request->blood_pressure,
                'sugar_level' => $request->sugar_level,
                'oxygen_saturation' => $request->oxygen_saturation,
                'credit_price_at_time' => 1000, // سنفترض السعر حالياً 1000، يمكن جلبها من ملف الإعدادات لاحقاً
                'total_paid' => $request->total_paid,
                'case_notes' => $request->case_notes,
            ]);

            // 4. ربط الخدمات المقدمة بالحالة
            $case->services()->attach($request->services);

            // 5. (اختياري الآن) خصم المخزون - سنقوم ببرمجة دالة الخصم لاحقاً لكي لا نعقد الكود حالياً
            
            return response()->json([
                'message' => 'تم تسجيل الحالة بنجاح',
                'case_id' => $case->id
            ], 201);
        });
    }
}