<?php

namespace App\Http\Controllers;

use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use App\Models\CustomerPoint;

class LoyaltyController extends Controller
{
    protected $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Calculate balance for a customer within a company
     */
    private function calculateBalance(CustomerPoint $transaction): int
    {
        $customerId = $transaction->customer_id;
        $email = $transaction->customer_email;
        $companyId = $transaction->company_id;

        $totalEarned = CustomerPoint::where('company_id', $companyId)
            ->where('transaction_type', 'earning')
            ->where('status', 'completed')   
            ->where(function($q) use ($customerId, $email) {
                if ($customerId) {
                    $q->where('customer_id', $customerId);
                } elseif ($email) {
                    $q->where('customer_email', $email);
                }
            })
            ->sum('points_earned');

        $totalRedeemed = CustomerPoint::where('company_id', $companyId)
            ->where('transaction_type', 'redemption')
            ->where('status', 'completed')   
            ->where(function($q) use ($customerId, $email) {
                if ($customerId) {
                    $q->where('customer_id', $customerId);
                } elseif ($email) {
                    $q->where('customer_email', $email);
                }
            })
            ->sum('points_earned');

        return  $totalEarned - $totalRedeemed;
    }


    


    /**
     * Mark an earning transaction as completed
     */
    // public function confirmEarning($transactionId)
    // {
    //     $transaction = CustomerPoint::where('transaction_id', $transactionId)->first();

    //     if (!$transaction) {
    //         return response()->json(['error' => 'Transaction not found'], 404);
    //     }

    //     $transaction->status = 'completed';
    //     $transaction->credited_at = now();
    //     $transaction->save();

    //     $balance = $this->calculateBalance($transaction);

    //     return response()->json([
    //         'transaction_type' => 'earning',
    //         'customer' => $transaction->customer_id ?? $transaction->customer_email,
    //         'company' => $transaction->company->company_name ?? null,
    //         'points' => $transaction->points_earned,
    //         'balance' => $transaction->balance,
    //         'status' => $transaction->status,
    //         'transaction_id' => $transaction->transaction_id,
    //         'date' => $transaction->created_at,
    //     ]);
    // }

    // /**
    //  * Mark a redemption transaction as completed
    //  */
    // public function confirmRedemption($transactionId)
    // {
    //     $transaction = CustomerPoint::where('transaction_id', $transactionId)->first();

    //     if (!$transaction) {
    //         return response()->json(['error' => 'Transaction not found'], 404);
    //     }

    //     $transaction->status = 'completed';
    //     $transaction->redeemed_at = now();
    //     $transaction->save();

    //     $balance = $this->calculateBalance($transaction);

    //     return response()->json([
    //         'transaction_type' => 'redemption',
    //         'customer' => $transaction->customer_id ?? $transaction->customer_email,
    //         'company' => $transaction->company->company_name ?? null,
    //         'points' => $transaction->points_earned,
    //         'balance' => $transaction->balance,
    //         'status' => $transaction->status,
    //         'transaction_id' => $transaction->transaction_id,
    //         'date' => $transaction->created_at,
    //     ]);
    // }

public function confirmEarning(string $transactionId)
    {
        $transaction = $this->loyaltyService->confirmEarning($transactionId);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found or already confirmed'], 404);
        }

        $balance = CustomerPoint::getCustomerBalance(
            $transaction->customer_id,
            $transaction->customer_email,
            $transaction->company_id
        );

        return response()->json([
            'message'        => 'Earning confirmed successfully',
            'transaction_id' => $transaction->transaction_id,
            'points'         => $transaction->points_earned,
            'balance'        => $balance,
            'status'         => $transaction->status,
            'customer'       => $transaction->customer_email ?? $transaction->customer_id,
            'company'        => $transaction->company->company_name ?? null,
            'date'           => $transaction->created_at->toDateTimeString(),
        ]);
    }

    public function confirmRedemption(string $transactionId)
    {
        $transaction = $this->loyaltyService->confirmRedemption($transactionId);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found, already confirmed, or insufficient points'], 404);
        }

        $balance = CustomerPoint::getCustomerBalance(
            $transaction->customer_id,
            $transaction->customer_email,
            $transaction->company_id
        );

        return response()->json([
            'message'        => 'Redemption confirmed successfully',
            'transaction_id' => $transaction->transaction_id,
            'points'         => $transaction->points_earned, // usually negative for redeem
            'balance'        => $balance,
            'status'         => $transaction->status,
            'customer'       => $transaction->customer_email ?? $transaction->customer_id,
            'company'        => $transaction->company->company_name ?? null,
            'date'           => $transaction->created_at->toDateTimeString(),
        ]);
    }

    
}