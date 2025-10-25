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
    
    public function calculatePoints(LoyaltyProgram $loyaltyProgram, float $purchaseAmount): array
    {
        $totalPoints = 0;
        $breakdown = [];

        $rules = $loyaltyProgram->rules()->where('is_active', true)->orderBy('id')->get();

        foreach ($rules as $rule) {
            $rulePoints = 0;

            if ($rule->min_purchase_amount && $purchaseAmount < $rule->min_purchase_amount) {
                continue;
            }

            switch ($rule->rule_type) {
                case 'purchase_based':
                    if ($rule->amount_per_point > 0) {
                        $rulePoints = floor($purchaseAmount / $rule->amount_per_point);
                    }
                    break;
                    
                case 'birthday_bonus':
                case 'referral_bonus':
                    $rulePoints = $rule->points_earned ?? 0;
                    break;
                    
                default:
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
    

    public function createEarningTransaction(array $data): \App\Models\CustomerPoint
    {
        $customerId    = $data['customer_id'] ?? null;
        $customerEmail = $data['customer_email'] ?? null;
        $companyId     = (int) $data['company_id'];
        $pointsEarned  = (int) $data['points_earned'];

        // ✅ COMPLETED-only balance BEFORE this earning
        $before = $this->getAvailableBalance($customerId, $customerEmail, $companyId);

        // Earn is finalized immediately (as per your setup)
        $after  = $before + $pointsEarned;

        return \App\Models\CustomerPoint::create([
            'customer_id'            => $customerId,
            'customer_email'         => $customerEmail,
            'company_id'             => $companyId,
            'loyalty_program_id'     => $data['loyalty_program_id'] ?? null,
            'transaction_id'         => \Illuminate\Support\Str::uuid(),
            'points_earned'          => $pointsEarned,                 // positive
            'purchase_amount'        => $data['purchase_amount'] ?? 0,
            'transaction_type'       => 'earning',
            'status'                 => 'completed',                    // finalized
            'balance'                => $after,                         // ✅ snapshot AFTER, completed-only
            'rule_breakdown'         => $data['rule_breakdown'] ?? [],
            'transaction_date'       => now(),
        ]);
    }


    public function checkRedemptionEligibility(?string $customerId, ?string $customerEmail, int $companyId, int $pointsToRedeem): array
    {
            $currentBalance = $this->getAvailableBalance($customerId, $customerEmail, $companyId);

    return [
        'eligible'        => $currentBalance >= $pointsToRedeem,
        'current_balance' => $currentBalance,
        'points_needed'   => max(0, $pointsToRedeem - $currentBalance),
    ];
    }


    
  public function createRedemptionTransaction(array $data): CustomerPoint
    {
        $customerId    = $data['customer_id'] ?? null;
        $customerEmail = $data['customer_email'] ?? null;
        $companyId     = $data['company_id'];
        $pointsDebit   = -abs($data['redeem_points']); // negative on purpose

        // Snapshot of available (completed-only) balance before applying
        $availableBalance = $this->getAvailableBalance($customerId, $customerEmail, $companyId);

        return CustomerPoint::create([
            'customer_id'            => $customerId,
            'customer_email'         => $customerEmail,
            'company_id'             => $companyId,
            'transaction_id'         => $data['transaction_id'] ?? Str::uuid(),
            'points_earned'          => $pointsDebit,              // negative but PENDING → no effect on totals
            'transaction_type'       => 'redemption',
            'status'                 => 'pending',
            'redemption_description' => $data['redemption_description'] ?? null,
            'transaction_date'       => now(),
            'balance'                => $availableBalance,         // snapshot for display
        ]);
    }



    public function getCustomerSummary(?string $customerId, ?string $customerEmail, int $companyId): array
    {
        $balance = $this->getCustomerPointBalance($customerId, $customerEmail, $companyId);
        $transactions = CustomerPoint::getCustomerTransactionHistory($customerId, $customerEmail, $companyId);

        return [
            'balance' => $balance,
            'transactions' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_id' => $transaction->transaction_id,
                    'transaction_type' => $transaction->transaction_type,
                    'points_earned' => $transaction->points_earned,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at->format('Y-m-d H:i:s'),
                    'redemption_description' => $transaction->redemption_description ?: 'Points earned from purchase',
                    'purchase_amount' => $transaction->purchase_amount,
                ];
            })->toArray()
        ];
    }

    public function generateTransactionQr(CustomerPoint $transaction, ?string $customPayload = null): string
    {
        $transaction = $transaction->fresh();

        $qrData = $customPayload ?? json_encode([
            'transaction_id'   => $transaction->transaction_id,
            'action'           => $transaction->transaction_type, 
            'customer_id'      => $transaction->customer_id,
            'customer_email'   => $transaction->customer_email,
            'company_id'       => $transaction->company_id,
            'company_name'     => $transaction->company->name ?? null,
            'points'           => $transaction->points_earned,
            'balance'          => $transaction->balance, 
            'status'           => $transaction->status,
            'date'             => now()->toDateTimeString(),
        ]);

        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->errorCorrection('M')
            ->generate($qrData);

        $qrFileName = 'qr-codes/' . $transaction->transaction_id . '.png';

        if (!Storage::disk('public')->exists('qr-codes')) {
            Storage::disk('public')->makeDirectory('qr-codes');
        }

        Storage::disk('public')->put($qrFileName, $qrCode);

        $transaction->update(['qr_code_path' => $qrFileName]);

        return asset('storage/' . $qrFileName);
    }



    public function confirmEarning(string $transactionId): ?CustomerPoint
    {
      
        $tx = CustomerPoint::where('transaction_id', $transactionId)
        ->where('transaction_type', 'earning')
        ->where('status', 'pending')
        ->first();

    if (!$tx) {
        return null;
    }

    $tx->status = 'completed';
    $tx->credited_at = now();
    $tx->save();

    return $tx->fresh();
    }


     public function confirmRedemption(string $transactionId): ?CustomerPoint
    {
        $tx = CustomerPoint::where('transaction_id', $transactionId)
            ->where('transaction_type', 'redemption')
            ->where('status', 'pending')
            ->lockForUpdate()
            ->first();

        if (!$tx) return null;

        $after = $this->getAvailableBalance($tx->customer_id, $tx->customer_email, $tx->company_id)
               + (int) $tx->points_earned; 

        $tx->status      = 'completed';
        $tx->redeemed_at = now();
        $tx->balance     = $after;
        $tx->save();

        return $tx->fresh();
    }


    public function getCustomerPointBalance(?string $customerId, ?string $customerEmail, int $companyId): int
    {
        $query = CustomerPoint::query()
            ->where('company_id', $companyId);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } elseif ($customerEmail) {
            $query->where('customer_email', $customerEmail);
        } else {
            return 0; 
        }

        $balance = $query->sum('points_earned');

        return (int) $balance;
    }

    public function getAvailableBalance(?string $customerId, ?string $customerEmail, int $companyId): int
    {
        $query = CustomerPoint::query()
            ->where('company_id', $companyId)
            ->where('status', 'completed'); 

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } elseif ($customerEmail) {
            $query->where('customer_email', $customerEmail);
        } else {
            return 0;
        }

        return (int) $query->sum('points_earned'); 
    }
    
}
