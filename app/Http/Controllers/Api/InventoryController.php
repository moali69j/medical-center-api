<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
public function index()
{
    // لارافيل هنا سيتكفل بجلب العناصر غير المحذوفة تلقائياً وبأعلى كفاءة وسرعة
    $items = InventoryItem::latest()->get();
    
    return response()->json($items);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'quantity' => 'required|numeric',
            'unit' => 'required|string',
            'threshold' => 'required|numeric',
            'is_measurable' => 'required|boolean',
            'cost_price' => 'required|numeric|min:0' // إضافة حقل التكلفة هنا
        ]);

        $item = InventoryItem::create($validated);
        return response()->json($item, 201);
    }

   // تعديل دالة الـ update لتستقبل المتغير المربوط بالـ Resource تلقائياً
public function update(Request $request, $inventory)
{
    // جلب العنصر باستخدام المسمى المطابق للمسار
    $item = InventoryItem::findOrFail($inventory);
    
    $request->validate([
        'action' => 'required|in:add,subtract',
        'amount' => 'required|numeric|min:0.1'
    ]);

    if ($request->action === 'add') {
        $item->quantity += $request->amount;
    } else {
        if ($item->quantity < $request->amount) {
            return response()->json(['message' => 'الكمية المراد خصمها أكبر من المتوفر!'], 422);
        }
        $item->quantity -= $request->amount;
    }

    $item->save();
    return response()->json(['message' => 'تم تحديث المخزون بنجاح', 'item' => $item]);
}

public function destroy($id)
{
    $item = InventoryItem::findOrFail($id);
    $item->delete(); // سيقوم بوضع تاريخ الحذف في الخلية فقط دون مسح حقيقي
    
    return response()->json(['message' => 'تم نقل المادة للأرشيف المخفي بنجاح']);
}
}