<?php

namespace App\Http\Controllers;

use App\Models\FinancialReport;
use Illuminate\Http\Request;

class FinancialReportController extends Controller
{


    public function index(Request $request){
        $userId = $request->user()->id;

        $transactions = FinancialReport::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        //  $summary = [
        //     'net_profit'  => FinancialReport::getNetProfit($userId),
        //     'income'      => FinancialReport::getTotalIncome($userId),
        //     'expense'     => FinancialReport::getTotalExpense($userId),
        //     'assets'      => FinancialReport::getTotalAssets($userId),
        //     'liabilities' => FinancialReport::getTotalLiabilities($userId),
        // ];

         $income = $transactions->where('type', 'income')->sum('amount');
        $expense = $transactions->where('type', 'expense')->sum('amount');
        $assets = $transactions->where('type', 'asset')->sum('amount');
        $liabilities = $transactions->where('type', 'liability')->sum('amount');

        $summary = [
            'income'      => (float) $income,
            'expense'     => (float) $expense,
            'assets'      => (float) $assets,
            'liabilities' => (float) $liabilities,
            'net_profit'  => (float) ($income - $expense),
        ];

        return response()->json([
            'transactions' => $transactions,
            'summary' => $summary,
        ]);
    }

 
    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_title' => 'required|string|max:255',
            'type'              => 'required|in:income,expense,asset,liability',
            'amount'            => 'required|numeric|min:0',
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


    // public function summary(Request $request)
    // {
    //     $userId = $request->user()->id;

    //     $summary = [
    //         'net_profit'  => FinancialReport::getNetProfit($userId),
    //         'income'      => FinancialReport::getTotalIncome($userId),
    //         'expense'     => FinancialReport::getTotalExpense($userId),
    //         'assets'      => FinancialReport::getTotalAssets($userId),
    //         'liabilities' => FinancialReport::getTotalLiabilities($userId),
    //     ];

    //     return response()->json($summary);
    // }
}