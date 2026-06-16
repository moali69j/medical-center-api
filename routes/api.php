<?php

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PatientController;

Route::apiResource('inventory', InventoryController::class);
Route::apiResource('services', ServiceController::class);
Route::get('patients/search', [PatientController::class, 'search']);
// مسار حفظ الحالة المتكاملة
Route::post('cases', [App\Http\Controllers\Api\CaseReportController::class, 'store']);
// routes/api.php
Route::put('settings/credit-price', function (Illuminate\Http\Request $request) {
    $validated = $request->validate([
        'credit_price' => 'required|numeric|min:1'
    ]);
    
    $setting = App\Models\Setting::updateOrCreate(
        ['key' => 'credit_price'],
        ['value' => $validated['credit_price']]
    );

    return response()->json([
        'message' => 'تم تحديث سعر الكريدت بنجاح',
        'value' => $setting->value
    ], 200);
});
// مسار جلب التقارير المالية المفلترة والتحليلات وإحصائيات الإكسل
Route::get('financial/reports', [App\Http\Controllers\Api\FinancialController::class, 'getReport']);

// مسارات إدارة المصاريف التشغيلية (إضافة، عرض، حذف ناعم)
Route::apiResource('expenses', App\Http\Controllers\Api\ExpenseController::class);
// مسار جلب تفاصيل وجداول مستحقات حصص الكادر الطبي المفلترة زمنياً
Route::get('financial/staff-reports', [App\Http\Controllers\Api\FinancialController::class, 'getStaffReport']);
// تأكد من وجود هذا السطر لفتح نقطة الاتصال لجدول المرضى بالكامل
Route::get('patients', [App\Http\Controllers\Api\PatientController::class, 'index']);

// أو إذا كنت تستخدم الـ Resource كاملاً تأكد أنه مكتوب بصيغة الجمع هكذا:
Route::apiResource('patients', App\Http\Controllers\Api\PatientController::class);