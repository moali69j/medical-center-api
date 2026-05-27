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