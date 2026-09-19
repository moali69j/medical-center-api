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

        // جلب الخدمات الفعالة فقط مع موادها غير المحذوفة ناعماً واحتساب تكلفة المواد لكل خدمة
        $services = Service::with(['materials' => function($q) {
            $q->whereNull('deleted_at'); 
        }])->get()->map(function ($service) use ($creditPrice) {
            $service->calculated_price = $service->credits_required * $creditPrice;
            
            $materialsCost = 0;
            foreach ($service->materials as $mat) {
                $materialsCost += ((float)$mat->cost_price * (float)$mat->pivot->quantity);
            }
            $service->total_materials_cost = $materialsCost;
            $service->expected_profit = $service->calculated_price - $materialsCost;

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
            'materials.*.id' => 'required|exists:inventory_items,id,deleted_at,NULL',
            'materials.*.quantity' => 'required|numeric|min:0.1'
        ]);

        $creditPriceSetting = \App\Models\Setting::where('key', 'credit_price')->first();
        $creditPrice = $creditPriceSetting ? (float)$creditPriceSetting->value : 1000.00;
        $sellingPrice = (float)$request->credits_required * $creditPrice;

        // احتساب إجمالي تكلفة المواد المرفقة
        $totalMaterialsCost = 0;
        if ($request->has('materials') && is_array($request->materials)) {
            foreach ($request->materials as $material) {
                $item = \App\Models\InventoryItem::find($material['id']);
                if ($item && $item->is_measurable) {
                    $totalMaterialsCost += ((float)$item->cost_price * (float)$material['quantity']);
                }
            }
        }

        // صمام أمان محاسبي: منع بيع الخدمة بأقل من تكلفة المواد المباشرة
        if ($totalMaterialsCost > 0 && $sellingPrice < $totalMaterialsCost) {
            $minCredits = ceil($totalMaterialsCost / $creditPrice);
            return response()->json([
                'message' => "خطأ في التسعير: سعر بيع الخدمة (" . number_format($sellingPrice) . " ل.س) أقل من تكلفة المواد المستهلكة (" . number_format($totalMaterialsCost) . " ل.س)! الحد الأدنى المطلوب لتغطية التكلفة هو ({$minCredits} نقطة)."
            ], 422);
        }

        return DB::transaction(function () use ($request) {
            $service = Service::create([
                'name' => $request->name,
                'credits_required' => $request->credits_required
            ]);

            if ($request->has('materials') && is_array($request->materials)) {
                foreach ($request->materials as $material) {
                    $item = \App\Models\InventoryItem::find($material['id']);
                    if ($item && $item->is_measurable) {
                        $service->materials()->attach($material['id'], [
                            'quantity' => $material['quantity']
                        ]);
                    }
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

        $creditPriceSetting = \App\Models\Setting::where('key', 'credit_price')->first();
        $creditPrice = $creditPriceSetting ? (float)$creditPriceSetting->value : 1000.00;
        $sellingPrice = (float)$request->credits_required * $creditPrice;

        // احتساب إجمالي تكلفة المواد المرفقة عند التعديل
        $totalMaterialsCost = 0;
        if ($request->has('materials') && is_array($request->materials)) {
            foreach ($request->materials as $material) {
                $item = \App\Models\InventoryItem::find($material['id']);
                if ($item && $item->is_measurable) {
                    $totalMaterialsCost += ((float)$item->cost_price * (float)$material['quantity']);
                }
            }
        }

        // صمام أمان محاسبي
        if ($totalMaterialsCost > 0 && $sellingPrice < $totalMaterialsCost) {
            $minCredits = ceil($totalMaterialsCost / $creditPrice);
            return response()->json([
                'message' => "خطأ في التسعير: سعر بيع الخدمة (" . number_format($sellingPrice) . " ل.س) أقل من تكلفة المواد المستهلكة (" . number_format($totalMaterialsCost) . " ل.س)! الحد الأدنى المطلوب لتغطية التكلفة هو ({$minCredits} نقطة)."
            ], 422);
        }

        return DB::transaction(function () use ($request, $service) {
            $serviceModel = Service::findOrFail($service);
            
            $serviceModel->update([
                'name' => $request->name,
                'credits_required' => $request->credits_required
            ]);

            $syncData = [];
            if ($request->has('materials') && is_array($request->materials)) {
                foreach ($request->materials as $material) {
                    $item = \App\Models\InventoryItem::find($material['id']);
                    if ($item && $item->is_measurable) {
                        $syncData[$material['id']] = ['quantity' => $material['quantity']];
                    }
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