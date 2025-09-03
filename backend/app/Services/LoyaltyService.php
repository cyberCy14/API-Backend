<?php

namespace App\Services;

use App\Models\CustomerPoint;
use App\Models\LoyaltyProgram;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class LoyaltyService
{
    /**
     * Calculate points based on loyalty program rules
     */
    public function calculatePoints(LoyaltyProgram $loyaltyProgram, float $purchaseAmount): array
{
    $totalPoints = 0;
    $breakdown = [];

    // Get all active rules for the loyalty program
    $rules = $loyaltyProgram->rules()->where('is_active', true)->orderBy('id')->get();

    foreach ($rules as $rule) {
        $rulePoints = 0;

        // Check if rule meets minimum purchase requirement
        if ($rule->min_purchase_amount && $purchaseAmount < $rule->min_purchase_amount) {
            continue;
        }

        switch ($rule->rule_type) {
            case 'purchase_based':
                // Calculate points based on amount per point
                if ($rule->amount_per_point > 0) {
                    $rulePoints = floor($purchaseAmount / $rule->amount_per_point);
                }
                break;
                
            case 'birthday_bonus':
            case 'referral_bonus':
                // Fixed points for special events
                $rulePoints = $rule->points_earned ?? 0;
                break;
                
            default:
                // For any other rule types, use points_earned
                $rulePoints = $rule->points_earned ?? 0;
                break;
        }

        if ($rulePoints > 0) {
            $totalPoints += $rulePoints;
            $breakdown[] = [
                'rule_name' => $rule->rule_name,
                'rule_type' => $rule->rule_type,
                'points' => $rulePoints,
                'description' => $rule->rule_name . ': ' . $rulePoints . ' points'
            ];
        }
    }

    return [
        'total_points' => $totalPoints,
        'breakdown' => $breakdown
    ];
}
    /**
     * Create earning transaction
     */
    public function createEarningTransaction(array $data): CustomerPoint
    {
        return CustomerPoint::create([
            'customer_id' => $data['customer_id'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'company_id' => $data['company_id'],
            'loyalty_program_id' => $data['loyalty_program_id'],
            'transaction_id' => Str::uuid(),
            'points_earned' => $data['points_earned'],
            'purchase_amount' => $data['purchase_amount'],
            'transaction_type' => 'earning',
            'status' => 'pending',
            'rule_breakdown' => $data['rule_breakdown'] ?? [],
            'transaction_date' => now(),
        ]);
    }

    /**
     * Check redemption eligibility
     */
    public function checkRedemptionEligibility(?string $customerId, ?string $customerEmail, int $companyId, int $pointsToRedeem): array
    {
        $currentBalance = CustomerPoint::getCustomerBalance($customerId, $customerEmail, $companyId);
        
        return [
            'eligible' => $currentBalance >= $pointsToRedeem,
            'current_balance' => $currentBalance,
            'points_needed' => max(0, $pointsToRedeem - $currentBalance)
        ];
    }

    /**
     * Create redemption transaction
     */
    public function createRedemptionTransaction(array $data): CustomerPoint
    {
        return CustomerPoint::create([
            'customer_id' => $data['customer_id'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'company_id' => $data['company_id'],
            'transaction_id' => Str::uuid(),
            'points_earned' => -abs($data['redeem_points']), // Negative for redemptions
            'transaction_type' => 'redemption',
            'status' => 'pending',
            'redemption_description' => $data['redemption_description'],
            'transaction_date' => now(),
        ]);
    }

    /**
     * Get customer summary
     */
    public function getCustomerSummary(?string $customerId, ?string $customerEmail, int $companyId): array
    {
        $balance = CustomerPoint::getCustomerBalance($customerId, $customerEmail, $companyId);
        $transactions = CustomerPoint::getCustomerTransactionHistory($customerId, $customerEmail, $companyId);

        return [
            'balance' => $balance,
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'type' => $transaction->transaction_type,
                    'points' => $transaction->points_earned,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'description' => $transaction->redemption_description ?: 'Points earned from purchase',
                    'purchase_amount' => $transaction->purchase_amount,
                ];
            })->toArray()
        ];
    }

    /**
     * Generate QR code for transaction
     */
    public function generateTransactionQr(CustomerPoint $transaction, string $webhookUrl): string
    {
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->errorCorrection('M')
            ->generate($webhookUrl);

        $qrFileName = 'qr-codes/' . $transaction->transaction_id . '.png';

        if (!Storage::disk('public')->exists('qr-codes')) {
            Storage::disk('public')->makeDirectory('qr-codes');
        }

        Storage::disk('public')->put($qrFileName, $qrCode);

        // Update the transaction with QR code path
        $transaction->update(['qr_code_path' => $qrFileName]);

        return asset('storage/' . $qrFileName);
    }

    /**
     * Confirm earning transaction
     */
    public function confirmEarning(string $transactionId): bool
    {
        $transaction = CustomerPoint::where('transaction_id', $transactionId)
            ->where('transaction_type', 'earning')
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return false;
        }

        return $transaction->creditPoints();
    }

    /**
     * Confirm redemption transaction
     */
    public function confirmRedemption(string $transactionId): bool
    {
        $transaction = CustomerPoint::where('transaction_id', $transactionId)
            ->where('transaction_type', 'redemption')
            ->where('status', 'pending')
            ->first();

        if (!$transaction) {
            return false;
        }

        return $transaction->redeemPoints();
    }
}