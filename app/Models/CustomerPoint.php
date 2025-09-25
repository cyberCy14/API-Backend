<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class CustomerPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'customer_email',
        'company_id',
        'loyalty_program_id',
        'transaction_id',
        'points_earned',
        'purchase_amount',
        'transaction_type',
        'status',
        'balance',
        'rule_breakdown',
        'credited_at',
        'redeemed_at',
        'redemption_description',
        'transaction_date',
        'qr_code_path',
        'total_points',
    ];

    protected $casts = [
        'points_earned' => 'integer',
        'purchase_amount' => 'decimal:2',
        'rule_breakdown' => 'array',
        'credited_at' => 'datetime',
        'redeemed_at' => 'datetime',
        'total_points' => 'float',
        'balance'  => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    // Updated scopes to handle both customer_id and email
    public function scopeForCustomer(Builder $query, ?string $customerId = null, ?string $email = null): Builder
    {
        return $query->where(function ($q) use ($customerId, $email) {
            if ($customerId) {
                $q->where('customer_id', $customerId);
            }
            if ($email) {
                $q->orWhere('customer_email', $email);
            }
        });
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeCredited(Builder $query): Builder
    {
        return $query->where('status', 'credited');
    }

    public function scopeRedeemed(Builder $query): Builder
    {
        return $query->where('status', 'redeemed');
    }

    public function scopeEarnings(Builder $query): Builder
    {
        return $query->where('transaction_type', 'earning');
    }

    public function scopeRedemptions(Builder $query): Builder
    {
        return $query->where('transaction_type', 'redemption');
    }

public static function getCustomerBalance(?string $customerId, ?string $email, int $companyId): int
{
    $earned = self::where('company_id',$companyId)
        ->where('transaction_type','earning')
        ->whereIn('status',['completed','credited'])
        ->when($customerId,fn($q)=>$q->where('customer_id',$customerId))
        ->when(!$customerId && $email,fn($q)=>$q->where('customer_email',$email))
        ->sum('points_earned');

    $redeemed = self::where('company_id',$companyId)
        ->where('transaction_type','redemption')
        ->whereIn('status',['completed','redeemed'])
        ->when($customerId,fn($q)=>$q->where('customer_id',$customerId))
        ->when(!$customerId && $email,fn($q)=>$q->where('customer_email',$email))
        ->sum('points_earned');

    return $earned - abs($redeemed);
}

    public static function getCustomerTransactionHistory(?string $customerId, ?string $email, int $companyId)
    {
        $query = self::where('company_id', $companyId)
            ->with(['loyaltyProgram', 'company'])
            ->orderBy('created_at', 'desc');
            
        if ($customerId) {
            $query->where('customer_id', $customerId);
        } elseif ($email) {
            $query->where('customer_email', $email);
        }

        return $query->get();
    }

        public function creditPoints(): bool
    {
        if ($this->status === 'pending' && $this->transaction_type === 'earning') {
            $this->update([
                'status' => 'credited',
                'credited_at' => now()
            ]);
            return true;
        }

        if ($this->transaction_type !== 'earning') {
            Log::error('Attempted to credit points on non-earning transaction', [
                'transaction_id' => $this->transaction_id,
                'transaction_type' => $this->transaction_type,
                'status' => $this->status
            ]);
        }
        return false;
    }

    public function redeemPoints(): bool
    {
        if ($this->status === 'pending' && $this->transaction_type === 'redemption') {
            // First verify customer has enough points for THIS SPECIFIC COMPANY
            $currentBalance = self::getCustomerBalance(
                $this->customer_id, 
                $this->customer_email, 
                $this->company_id
            );
            $pointsToRedeem = abs($this->points_earned);

            if ($currentBalance < $pointsToRedeem) {
                Log::error('Insufficient points for redemption', [
                    'transaction_id' => $this->transaction_id,
                    'customer_id' => $this->customer_id,
                    'customer_email' => $this->customer_email,
                    'company_id' => $this->company_id,
                    'current_balance' => $currentBalance,
                    'points_to_redeem' => $pointsToRedeem
                ]);
                return false;
            }

            $this->update([
                'status' => 'redeemed',
                'redeemed_at' => now()
            ]);
            return true;
        }
        return false;
    }


    public function getCustomerIdentifierAttribute(): ?string
    {
        return $this->customer_id ?: $this->customer_email;
    }

    /**
     * Check if customer has valid identifier
     */
    public function hasValidCustomerIdentifier(): bool
    {
        return !empty($this->customer_id) || !empty($this->customer_email);
    }




    protected static function booted()
    {
        static::created(function ($customerPoint) {
            $customerPoint->updateCompanyBalance();
        });

        static::updated(function ($customerPoint) {
            $customerPoint->updateCompanyBalance();
        });
    }

    /**
     * Helper to update the balance in customer_company_balances table
     */
    protected function updateCompanyBalance()
    {
        if ($this->hasValidCustomerIdentifier() && $this->company_id) {
            \App\Models\CustomerCompanyBalance::updateOrCreate(
                [
                    'customer_id' => $this->customer_id, 
                    'company_id'  => $this->company_id
                ],
                [
                    'total_balance' => $this->balance
                ]
            );
        }
}
}