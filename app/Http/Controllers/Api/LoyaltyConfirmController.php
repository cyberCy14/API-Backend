<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerPoint;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;

class LoyaltyConfirmController extends Controller
{
    public function confirmRedeem(string $transactionId, LoyaltyService $service): JsonResponse
    {
        try {
            $tx = $service->confirmRedemption($transactionId);
            if (!$tx) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Pending redemption not found for this transaction.',
                    'transaction_id' => $transactionId,
                ], 404);
            }

            return response()->json([
                'ok'             => true,
                'affected'       => 1,
                'status'         => $tx->status,
                'transaction_id' => $tx->transaction_id,
                'transaction'    => $tx,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('confirmRedeem failed', ['tx' => $transactionId, 'err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

public function cancelRedeem(string $transactionId): \Illuminate\Http\JsonResponse
{
    try {
        $affected = \DB::table('customer_points')
            ->where('transaction_id', $transactionId)
            ->where('transaction_type', 'redemption')
            ->where('status', 'pending')
            ->update([
                'status'     => 'cancelled',
                'updated_at' => now(),
            ]);

        $tx = \App\Models\CustomerPoint::where('transaction_id', $transactionId)->first();

        if (!$tx) {
            return response()->json([
                'ok' => false,
                'error' => 'Transaction not found',
                'transaction_id' => $transactionId,
            ], 404);
        }

        return response()->json([
            'ok'             => true,
            'affected'       => $affected,
            'status'         => 'cancelled',
            'transaction_id' => $tx->transaction_id,
            'transaction'    => $tx,
        ], 200);

    } catch (\Throwable $e) {
        return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
    }
}




    public function confirmEarning(string $transactionId): JsonResponse
    {
        try {
            $affected = \DB::table('customer_points')
                ->where('transaction_id', $transactionId)
                ->where('transaction_type', 'earning')
                ->where('status', 'pending')
                ->update([
                    'status'      => 'completed',
                    'credited_at' => now(),
                    'updated_at'  => now(),
                ]);

            $tx = CustomerPoint::where('transaction_id', $transactionId)->first();
            if (!$tx) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Transaction not found.',
                    'transaction_id' => $transactionId,
                ], 404);
            }

            return response()->json([
                'ok'             => true,
                'affected'       => $affected,
                'status'         => $tx->status,
                'transaction_id' => $tx->transaction_id,
                'transaction'    => $tx,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('confirmEarning failed', ['tx' => $transactionId, 'err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function cancelEarning(string $transactionId): JsonResponse
    {
        try {
            $affected = \DB::table('customer_points')
                ->where('transaction_id', $transactionId)
                ->where('transaction_type', 'earning')
                ->where('status', 'pending')
                ->update([
                    'status'     => 'expired', // use expired for cancel
                    'updated_at' => now(),
                ]);

            $tx = CustomerPoint::where('transaction_id', $transactionId)->first();
            if (!$tx) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Transaction not found.',
                    'transaction_id' => $transactionId,
                ], 404);
            }

            return response()->json([
                'ok'             => true,
                'affected'       => $affected,
                'status'         => $tx->status,
                'transaction_id' => $tx->transaction_id,
                'transaction'    => $tx,
            ], 200);
        } catch (\Throwable $e) {
            \Log::error('cancelEarning failed', ['tx' => $transactionId, 'err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function showTx(string $transactionId): JsonResponse
    {
        try {
            $tx = CustomerPoint::where('transaction_id', $transactionId)->first();
            if (!$tx) {
                return response()->json(['ok' => false, 'error' => 'Not found'], 404);
            }
            return response()->json(['ok' => true, 'transaction' => $tx], 200);
        } catch (\Throwable $e) {
            \Log::error('showTx failed', ['tx' => $transactionId, 'err' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
