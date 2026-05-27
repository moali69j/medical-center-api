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
    // جلب سعر الكريدت الحالي من جدول الإعدادات
    // استبدل السطر القديم بهذا:
$creditPriceSetting = \App\Models\Setting::where('key', 'credit_price')->first();
$creditPrice = $creditPriceSetting ? (float)$creditPriceSetting->value : 1000.00;

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
    $request->validate([
        'name' => 'required|string|max:255',
        'credits_required' => 'required|integer|min:0',
        'materials' => 'nullable|array',
        'materials.*.id' => 'required|exists:inventory_items,id',
        'materials.*.quantity' => 'required|numeric|min:0.1'
    ]);

    return DB::transaction(function () use ($request) {
        // 1. إنشاء الخدمة
        $service = Service::create([
            'name' => $request->name,
            'credits_required' => $request->credits_required
        ]);

        // 2. ربط المواد عبر الجدول الوسيط إذا أرسلها المستخدم
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

    public function update(Request $request, Service $service)
    {
        $service->update($request->all());
        return response()->json($service);
    }
}