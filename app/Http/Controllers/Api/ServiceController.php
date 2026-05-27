<?php
 

 namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
   public function index()
{
    // جلب سعر الكريدت الحالي من جدول الإعدادات
    $creditPrice = (float) Setting::get('credit_price', 1000);

    // جلب الخدمات مع موادها وحساب السعر ديناميكياً لكل خدمة
    $services = Service::with('materials')->get()->map(function ($service) use ($creditPrice) {
        $service->calculated_price = $service->credits_required * $creditPrice;
        return $service;
    });

    return response()->json([
        'services' => $services,
        'current_credit_price' => $creditPrice
    ]);
}

   public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string',
        'credits_required' => 'required|integer',
        'materials' => 'nullable|array', // مصفوفة المواد: [['id' => 1, 'quantity' => 2]]
    ]);

    return DB::transaction(function () use ($request, $validated) {
        // 1. إنشاء الخدمة
        $service = Service::create([
            'name' => $validated['name'],
            'credits_required' => $validated['credits_required']
        ]);

        // 2. ربط المواد بالخدمة إذا وُجدت
        if (!empty($request->materials)) {
            foreach ($request->materials as $material) {
                $service->materials()->attach($material['id'], ['quantity' => $material['quantity']]);
            }
        }

        return response()->json($service->load('materials'), 201);
    });
}

    public function update(Request $request, Service $service)
    {
        $service->update($request->all());
        return response()->json($service);
    }
}