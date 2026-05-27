<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryItem;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    // عرض كل المواد
    public function index()
    {
        return response()->json(InventoryItem::all());
    }

    // إضافة مادة جديدة للمخزن أو زيادة الكمية
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'quantity' => 'required|numeric',
            'unit' => 'required|string',
            'threshold' => 'required|numeric',
            'is_measurable' => 'required|boolean'
        ]);

        $item = InventoryItem::create($validated);
        return response()->json($item, 201);
    }

    // تحديث بيانات مادة أو تعديل المخزون يدوياً

public function update(Request $request, InventoryItem $inventory)
{
    $request->validate([
        'action' => 'required|in:add,subtract',
        'amount' => 'required|numeric|min:0.1'
    ]);

    if ($request->action === 'add') {
        $inventory->quantity += $request->amount;
    } else {
        // نمنع الخصم ليكون تحت الصفر
        if ($inventory->quantity < $request->amount) {
            return response()->json(['message' => 'الكمية المراد خصمها أكبر من المتوفر!'], 422);
        }
        $inventory->quantity -= $request->amount;
    }

    $inventory->save();

    return response()->json([
        'message' => 'تم تحديث المخزون بنجاح',
        'item' => $inventory
    ]);
}

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();
        return response()->json(null, 204);
    }
}