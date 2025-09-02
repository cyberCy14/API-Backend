<?php

namespace App\Services;

use App\Models\LoyaltyProgram;
use App\Models\CustomerPoint;
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

        $rules = $loyaltyProgram->rules()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('active_from_date')
                      ->orWhere('active_from_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('active_to_date')
                      ->orWhere('active_to_date', '>=', now());
            })
            ->get();

        Log::info('Found rules for calculation', ['count' => $rules->count(), 'program_id' => $loyaltyProgram->id]);

        foreach ($rules as $rule) {
            $rulePoints = $this->calculateRulePoints($rule, $purchaseAmount);

            if ($rulePoints > 0) {
                $totalPoints += $rulePoints;
                $breakdown[] = [
                    'rule_name' => $rule->rule_name,
                    'rule_type' => $rule->rule_type,
                    'points' => $rulePoints,
                ];
            }
        }

        return [
            'total_points' => $totalPoints,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Calculate points for a specific rule
     */
    private function calculateRulePoints($rule, float $purchaseAmount): int
    {
        // Check minimum purchase requirement
        if ($rule->min_purchase_amount && $purchaseAmount < $rule->min_purchase_amount) {
            return 0;
        }

        switch ($rule->rule_type) {
            case 'purchase_based':
                if ($rule->amount_per_point > 0) {
                    return floor($purchaseAmount / $rule->amount_per_point) * $rule->points_earned;
                }
                break;

            case 'first_purchase':
                return $rule->points_earned;

            case 'milestone':
                if ($purchaseAmount >= ($rule->milestone_amount ?? 1000)) {
                    return $rule->points_earned;
                }
                break;
        }

        return 0;
    }

    /**
     * Create a pending earning transaction
     */
    public function createEarningTransaction(array $data): CustomerPoint
{
    $transactionId = 'TXN-' . strtoupper(Str::random(10));

    Log::info('Creating earning transaction', [
        'customer_email' => $data['customer_email'],
        'company_id' => $data['company_id'],
        'points_earned' => $data['points_earned'],
        'transaction_id' => $transactionId
    ]);

    return CustomerPoint::create([
        'customer_email' => $data['customer_email'],
        'company_id' => $data['company_id'],
        'loyalty_program_id' => $data['loyalty_program_id'],
        'points_earned' => $data['points_earned'],
        'purchase_amount' => $data['purchase_amount'],
        'transaction_id' => $transactionId,
        'transaction_type' => 'earning',
        'status' => 'pending',
        'rule_breakdown' => json_encode($data['rule_breakdown'] ?? []),
        'created_by' => $data['created_by'],
    ]);
}

    /**
     * Create a pending redemption transaction
     */
    public function createRedemptionTransaction(array $data): CustomerPoint
{
    // First check if customer has enough points for THIS SPECIFIC COMPANY
    $eligibility = $this->checkRedemptionEligibility(
        $data['customer_email'], 
        $data['company_id'], 
        $data['redeem_points']
    );
    
    if (!$eligibility['eligible']) {
        throw new \Exception("Insufficient points for redemption. Customer has {$eligibility['current_balance']} points but trying to redeem {$eligibility['required_points']}");
    }
    
    $transactionId = 'RED-' . strtoupper(Str::random(10));

    Log::info('Creating redemption transaction', [
        'customer_email' => $data['customer_email'],
        'company_id' => $data['company_id'],
        'redeem_points' => $data['redeem_points'],
        'customer_balance' => $eligibility['current_balance'],
        'transaction_id' => $transactionId
    ]);

    return CustomerPoint::create([
        'customer_email' => $data['customer_email'],
        'company_id' => $data['company_id'],
        'loyalty_program_id' => null, // Redemptions might not be tied to specific programs
        'points_earned' => -abs($data['redeem_points']), // Ensure negative value
        'purchase_amount' => null,
        'transaction_id' => $transactionId,
        'transaction_type' => 'redemption',
        'status' => 'pending',
        'redemption_description' => $data['redemption_description'],
        'created_by' => $data['created_by'],
    ]);
}

    /**
     * Generate QR code for a transaction
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

        // Update transaction with QR code path
        $transaction->update(['qr_code_path' => $qrFileName]);

        return asset('storage/' . $qrFileName);
    }

    /**
     * Check if customer has sufficient balance for redemption
     */
    public function checkRedemptionEligibility(string $customerEmail, int $companyId, int $redeemPoints): array
    {
        $currentBalance = CustomerPoint::getCustomerBalance($customerEmail, $companyId);
        
        Log::info('Checking redemption eligibility', [
            'customer_email' => $customerEmail,
            'company_id' => $companyId,
            'current_balance' => $currentBalance,
            'redeem_points' => $redeemPoints
        ]);
        
        return [
            'eligible' => $currentBalance >= $redeemPoints,
            'current_balance' => $currentBalance,
            'required_points' => $redeemPoints,
            'shortfall' => max(0, $redeemPoints - $currentBalance)
        ];
    }

    /**
     * Get customer summary information
     */
    public function getCustomerSummary(string $customerEmail, int $companyId): array
    {
        $balance = CustomerPoint::getCustomerBalance($customerEmail, $companyId);
        $transactions = CustomerPoint::getCustomerTransactionHistory($customerEmail, $companyId);
        
        // Calculate totals using company-specific data
        $totalEarned = $transactions->where('transaction_type', 'earning')
            ->where('status', 'completed')
            ->sum('points_earned');
            
        $totalRedeemed = abs($transactions->where('transaction_type', 'redemption')
            ->where('status', 'completed')
            ->sum('points_earned'));
        
        Log::info('Customer summary generated', [
            'customer_email' => $customerEmail,
            'company_id' => $companyId,
            'balance' => $balance,
            'total_earned' => $totalEarned,
            'total_redeemed' => $totalRedeemed,
            'transaction_count' => $transactions->count()
        ]);
        
        return [
            'customer_email' => $customerEmail,
            'company_id' => $companyId,
            'balance' => $balance,
            'total_transactions' => $transactions->count(),
            'transactions' => $transactions->toArray(),
            'total_earned' => $totalEarned,
            'total_redeemed' => $totalRedeemed,
        ];
    }
}