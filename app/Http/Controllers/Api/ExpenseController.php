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
            'transaction_type' => 'nullable|in:expense,deposit',
            'notes' => 'nullable|string',
            'case_id' => 'nullable|exists:case_reports,id'
        ]);

        $transactionType = $validated['transaction_type'] ?? (
            (str_contains($validated['category'], 'إيداع') || str_contains($validated['category'], 'تغذية'))
                ? 'deposit' 
                : 'expense'
        );

        $expense = Expense::create([
            'amount' => $validated['amount'],
            'transaction_type' => $transactionType,
            'category' => $validated['category'],
            'notes' => $validated['notes'] ?? null
        ]);

        if (isset($validated['case_id'])) {
            \App\Models\CaseReport::where('id', $validated['case_id'])->update(['is_paid_to_staff' => true]);
        }

        $isDeposit = ($transactionType === 'deposit');

        return response()->json([
            'message' => $isDeposit ? 'تم إيداع الكاش في الصندوق وتغذية الخزينة بنجاح' : 'تم تسجيل قيد الصرف بنجاح من الخزنة',
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