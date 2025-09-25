<?php

namespace App\Http\Controllers;

use App\Models\FinancialReport;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{
   
    public function index(Request $request)
    {
        $transactions = FinancialReport::where('user_id', $request->user()->id)
            ->orderBy('transaction_date', 'desc')
            ->get();

        return response()->json($transactions);
    }

 
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_title' => 'required|string|max:255',
            'type'              => 'required|in:income,expense,asset,liability',
            'amount'            => 'required|numeric|min:0',
            'transaction_date'  => 'required|date_format:Y-m-d H:i:s',
        ]);

        $validated['user_id'] = $request->user()->id;

        $transaction = FinancialReport::create($validated);

        return response()->json([
            'message' => 'Transaction added successfully',
            'transaction' => $transaction
        ], 201);
    }

      public function show(FinancialReport $financialReport, Request $request)
    {
        if ($financialReport->user_id !== $request->user()->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($financialReport);
    }


    public function summary(Request $request)
    {
        $userId = $request->user()->id;

        $summary = [
            'net_profit'  => FinancialReport::getNetProfit($userId),
            'income'      => FinancialReport::getTotalIncome($userId),
            'expense'     => FinancialReport::getTotalExpense($userId),
            'assets'      => FinancialReport::getTotalAssets($userId),
            'liabilities' => FinancialReport::getTotalLiabilities($userId),
        ];

        return response()->json($summary);
    }
}