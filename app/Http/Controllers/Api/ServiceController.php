<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    public function index()
{
    $creditPriceSetting = \App\Models\Setting::where('key', 'credit_price')->first();
    $creditPrice = $creditPriceSetting ? (float)$creditPriceSetting->value : 1000.00;

    // جلب الخدمات الفعالة فقط مع موادها غير المحذوفة ناعماً
    $services = Service::with(['materials' => function($q) {
        $q->whereNull('deleted_at'); 
    }])->get()->map(function ($service) use ($creditPrice) {
        $service->calculated_price = $service->credits_required * $creditPrice;
        return $service;
    });

    // إرسال الاستجابة بالهيكل الدقيق الذي يقرأه ملف Services.jsx لديك
    return response()->json([
        'services' => $services,
        'current_credit_price' => $creditPrice
    ]);
}

public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'credits_required' => 'required|integer|min:0',
        'materials' => 'nullable|array',
        // حماية: نتحقق من وجود المادة في المواد الفعالة فقط (غير المحذوفة ناعماً)
        'materials.*.id' => 'required|exists:inventory_items,id,deleted_at,NULL',
        'materials.*.quantity' => 'required|numeric|min:0.1'
    ]);

    return DB::transaction(function () use ($request) {
        $service = Service::create([
            'name' => $request->name,
            'credits_required' => $request->credits_required
        ]);

        if ($request->has('materials') && is_array($request->materials)) {
            foreach ($request->materials as $material) {
                $service->materials()->attach($material['id'], [
                    'quantity' => $material['quantity']
                ]);
            }
        }

        return response()->json([
            'message' => 'تم إنشاء الخدمة وربط مستلزماتها بنجاح',
            'service' => $service->load('materials')
        ], 201);
    });
}
public function update(Request $request, $service)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'credits_required' => 'required|integer|min:0',
        'materials' => 'nullable|array'
    ]);

    return DB::transaction(function () use ($request, $service) {
        // البحث باستخدام المتغير المطابق للمسار
        $serviceModel = Service::findOrFail($service);
        
        $serviceModel->update([
            'name' => $request->name,
            'credits_required' => $request->credits_required
        ]);

        $syncData = [];
        if ($request->has('materials') && is_array($request->materials)) {
            foreach ($request->materials as $material) {
                $syncData[$material['id']] = ['quantity' => $material['quantity']];
            }
        }
        $serviceModel->materials()->sync($syncData);

        return response()->json(['message' => 'تم تحديث الخدمة ومستلزماتها بنجاح']);
    });
}

public function destroy($id)
{
    $service = Service::findOrFail($id);
    $service->delete(); // سيقوم بوضع تاريخ الحذف في الخلية فقط دون مسح حقيقي
    
    return response()->json(['message' => 'تم إخفاء وأرشفة الخدمة الطبية بنجاح']);
}
}