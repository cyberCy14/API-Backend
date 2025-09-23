<?php
namespace App\Http\Controllers;

use App\Models\CustomerPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LoyaltyWebhookController extends Controller
{
    /**
     * Show confirmation form when QR code is scanned
     */
    public function showConfirmation(string $transactionId)
    {
        Log::info("Showing confirmation for transaction ID: $transactionId");
        
        $customerPoint = CustomerPoint::where('transaction_id', $transactionId)
            ->whereIn('transaction_type', ['earning', 'redemption'])
            ->first();

        if (!$customerPoint) {
            Log::warning("Transaction not found for ID: $transactionId");
            return view('loyalty.error', [
                'message' => 'Transaction not found',
                'error_code' => 404
            ]);
        }

        // Check if already processed
        if ($customerPoint->status === 'credited' || $customerPoint->status === 'redeemed') {
            Log::info("Transaction already processed for ID: $transactionId");
            return view('loyalty.already-processed', [
                'transaction' => $customerPoint,
                'message' => $customerPoint->transaction_type === 'earning' 
                    ? 'Points already credited' 
                    : 'Points already redeemed'
            ]);
        }

        return view('loyalty.confirm', [
            'transaction' => $customerPoint,
            'customer' => $customerPoint->customer,
            'points' => $customerPoint->points,
            'transaction_type' => $customerPoint->transaction_type
        ]);
    }

    /**
     * Confirm earning points
     */
    public function confirmEarning(string $transactionId)
    {
        Log::info("Confirming earning for transaction ID: $transactionId");
        
        $customerPoint = CustomerPoint::where('transaction_id', $transactionId)
            ->where('transaction_type', 'earning')
            ->first();

        if (!$customerPoint) {
            Log::warning("Earning transaction not found for ID: $transactionId");
            return response()->json(['message' => 'Earning transaction not found'], 404);
        }

        if ($customerPoint->status === 'credited') {
            Log::info("Earning already credited for transaction ID: $transactionId");
            return response()->json(['message' => 'Earning already credited'], 200);
        }

        if ($customerPoint->creditPoints()) {
            Log::info("Earning credited successfully for transaction ID: $transactionId");
            
            // Delete QR code after successful processing
            $this->deleteQRCode($customerPoint, $transactionId);
            
            return response()->json([
                'success' => true,
                'message' => 'Points credited successfully!',
                'transaction' => $customerPoint->fresh(),
                'redirect_url' => route('loyalty.success', $transactionId)
            ], 200);
        }

        Log::error("Failed to credit earning for transaction ID: $transactionId");
        return response()->json(['message' => 'Failed to credit earning'], 500);
    }

    /**
     * Confirm redemption points
     */
    public function confirmRedemption(string $transactionId)
    {
        Log::info("Confirming redemption for transaction ID: $transactionId");
        
        $customerPoint = CustomerPoint::where('transaction_id', $transactionId)
            ->where('transaction_type', 'redemption')
            ->first();

        if (!$customerPoint) {
            Log::warning("Redemption transaction not found for ID: $transactionId");
            return response()->json(['message' => 'Redemption transaction not found'], 404);
        }

        if ($customerPoint->status === 'redeemed') {
            Log::info("Redemption already processed for transaction ID: $transactionId");
            return response()->json(['message' => 'Redemption already processed'], 200);
        }

        if ($customerPoint->redeemPoints()) {
            Log::info("Redemption processed successfully for transaction ID: $transactionId");
            
            // Delete QR code after successful processing
            $this->deleteQRCode($customerPoint, $transactionId);
            
            return response()->json([
                'success' => true,
                'message' => 'Points redeemed successfully!',
                'transaction' => $customerPoint->fresh(),
                'redirect_url' => route('loyalty.success', $transactionId)
            ], 200);
        }

        Log::error("Failed to process redemption for transaction ID: $transactionId");
        return response()->json(['message' => 'Failed to process redemption'], 500);
    }

    /**
     * Show success page
     */
    public function showSuccess(string $transactionId)
    {
        $customerPoint = CustomerPoint::where('transaction_id', $transactionId)->first();
        
        if (!$customerPoint) {
            return view('loyalty.error', [
                'message' => 'Transaction not found',
                'error_code' => 404
            ]);
        }

        return view('loyalty.success', [
            'transaction' => $customerPoint,
            'customer' => $customerPoint->customer
        ]);
    }

    /**
     * Delete QR code helper method
     */
    private function deleteQRCode($customerPoint, $transactionId)
    {
        if ($customerPoint->qr_code_path && Storage::disk('public')->exists($customerPoint->qr_code_path)) {
            Storage::disk('public')->delete($customerPoint->qr_code_path);
            $customerPoint->update(['qr_code_path' => null]);
            Log::info("QR code deleted for transaction ID: $transactionId");
        }
    }
}