<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    // 1. عرض كافة المصاريف التشغيلية المسجلة
    public function index()
    {
        return response()->json(Expense::latest()->get());
    }

    // 2. تسجيل مصروف جديد (رواتب، فواتير، صيانة، مشتريات)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'category' => 'required|string',
            'notes' => 'nullable|string'
        ]);

        $expense = Expense::create($validated);

        return response()->json([
            'message' => 'تم تسجيل المصروف بنجاح في الخزنة الحالية',
            'expense' => $expense
        ], 201);
    }

    // 3. عرض تفاصيل مصروف معين (اختياري)
    public function show(Expense $expense)
    {
        return response()->json($expense);
    }

    // 4. حذف قيد مصروف من الخزنة في حال الإدخال الخاطئ
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json(['message' => 'تم حذف قيد المصروف بنجاح من السجلات']);
    }
}