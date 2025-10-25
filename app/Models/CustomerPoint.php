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
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(\App\Models\LoyaltyProgram::class, 'loyalty_program_id');
    }

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

    public static function availableBalance(?string $customerId, ?string $email, int $companyId): int
    {
        $q = self::query()->where('company_id', $companyId)->where('status', 'completed');

        if ($customerId) $q->where('customer_id', $customerId);
        elseif ($email) $q->where('customer_email', $email);
        else return 0;

        return (int) $q->sum('points_earned'); 
    }

    public function getCustomerSummary(?string $customerId, ?string $customerEmail, int $companyId): array
    {
        $balance = self::availableBalance($customerId, $customerEmail, $companyId);

        $transactions = CustomerPoint::getCustomerTransactionHistory($customerId, $customerEmail, $companyId);

        return [
            'balance' => $balance,
            'transactions' => $transactions->map(function ($t) {
                return [
                    'id' => $t->id,
                    'transaction_id' => $t->transaction_id,
                    'transaction_type' => $t->transaction_type,
                    'points_earned' => $t->points_earned,
                    'status' => $t->status,
                    'created_at' => $t->created_at->format('Y-m-d H:i:s'),
                    'redemption_description' => $t->redemption_description ?: 'Points earned from purchase',
                    'purchase_amount' => $t->purchase_amount,
                    'balance' => $t->balance, 
                ];
            })->toArray()
        ];
    }


    public function scopeCompleted(Builder $q): Builder
    {
        return $q->where('status', 'completed');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->where('status', 'pending');
    }

    public function scopeCancelled(Builder $q): Builder
    {
        return $q->where('status', 'cancelled');
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
        // return $query->where('status', '!=', 'cancelled')->get();
    }

    public static function getCustomerTransactionHistoryForCustomer(?string $customerId, ?string $email, int $companyId)
    {
        $query = self::where('company_id', $companyId)
            ->visibleToCustomer()       
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

  
    public function hasValidCustomerIdentifier(): bool
    {
        return !empty($this->customer_id) || !empty($this->customer_email);
    }


    protected static function booted()
    {
        static::created(fn ($cp) => $cp->updateCompanyBalance());
        static::updated(fn ($cp) => $cp->updateCompanyBalance());
    }

    protected function updateCompanyBalance()
    {
        if ($this->status !== 'completed') return;

        if ($this->hasValidCustomerIdentifier() && $this->company_id) {
            $currentTotalBalance = self::availableBalance(
                $this->customer_id,
                $this->customer_email,
                $this->company_id
            );

            \App\Models\CustomerCompanyBalance::updateOrCreate(
                ['customer_id' => $this->customer_id, 'company_id' => $this->company_id],
                ['total_balance' => $currentTotalBalance]
            );
        }
    }

    public function getStatusLabelAttribute()
    {
        return $this->attributes['status'] === 'expired'
            ? 'cancelled'
            : $this->attributes['status'];
    }

    public function scopeVisibleToCustomer($query)
    {
        return $query->whereIn('status', ['completed']);
    }

}
