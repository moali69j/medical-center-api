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
    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $inventoryItem->update($request->all());
        return response()->json($inventoryItem);
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        $inventoryItem->delete();
        return response()->json(null, 204);
    }
}