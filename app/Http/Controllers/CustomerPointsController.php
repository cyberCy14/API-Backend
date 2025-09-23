<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomerPoint;

class CustomerPointsController extends Controller
{
    public function index($customer_id)
    {
        $points = CustomerPoint::where('customer_id', $customer_id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'transactions' => $points
        ]);
    }


    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'customer_id'      => 'required|integer',
    //         'company_id'       => 'required|integer|exists:companies,id',
    //         'transaction_type' => 'required|in:earning,redemption',
    //         'points_earned'    => 'required|integer|min:1',
    //     ]);

    //     $customerId      = $request->customer_id;
    //     $transactionType = $request->transaction_type;
    //     $pointsEarned    = $request->points_earned;

    //     $lastTransaction = CustomerPoint::where('customer_id', $customerId)
    //         ->orderBy('created_at', 'desc')
    //         ->first();

    //     $lastBalance = $lastTransaction ? $lastTransaction->balance : 0;

    //     $pointsChange = ($transactionType === 'earning')
    //         ? $pointsEarned
    //         : -$pointsEarned;

    //     $newBalance = $lastBalance + $pointsChange;

    //     if ($newBalance < 0) {
    //         return response()->json([
    //             'error' => 'Insufficient balance for redemption.'
    //         ], 400);
    //     }

    //     $transaction = CustomerPoint::create([
    //         'customer_id'      => $customerId,
    //         'company_id'       => $request->company_id,
    //         'transaction_id'   => $request->transaction_id ?? uniqid('txn_'),
    //         'loyalty_program_id' => $request->loyalty_program_id,
    //         'transaction_type' => $transactionType,
    //         'points_earned'    => $pointsEarned,   
    //         'balance'          => $newBalance,    
    //         'purchase_amount'  => $request->purchase_amount ?? 0,
    //         'credited_at'      => $transactionType === 'earning' ? now() : null,
    //         'redeemed_at'      => $transactionType === 'redemption' ? now() : null,
    //         'transaction_date' => now(),
    //         'status'           => 'completed',
    //     ]);

    //     return response()->json([
    //         'message'     => 'Transaction recorded successfully.',
    //         'transaction' => $transaction,
    //         'new_balance' => $newBalance,
    //     ], 201);
    // }

    
}