<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgramRule extends Model
{
    use HasFactory;
    
    protected $table = 'loyalty_program_rules';
    
    protected $fillable = [
        'loyalty_program_id',
        'rule_name',
        'rule_type',
        'points_earned',
        'amount_per_point',
        'min_purchase_amount',
        'product_category_id',
        'product_item_id',
        'usage_limit',
        'is_active',
        'active_from_date',
        'active_to_date',
    ];

    protected $casts = [
        'points_earned' => 'decimal:2',     
        'amount_per_point' => 'decimal:2',  
        'is_active' => 'boolean',
        'active_from_date' => 'date',
        'active_to_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'min_purchase_amount' => 'decimal:2',
        'usage_limit' => 'integer',
    ];

    public function calculatePoints(float $purchaseAmount, ?int $categoryId = null, ?int $itemId = null): float
    {
        if (!$this->is_active) {
            return 0;
        }

        $now = now();
        if ($this->active_from_date && $now->lt($this->active_from_date)) {
            return 0;
        }
        if ($this->active_to_date && $now->gt($this->active_to_date)) {
            return 0;
        }

        if ($this->min_purchase_amount && $purchaseAmount < $this->min_purchase_amount) {
            return 0;
        }

        if ($this->product_category_id && $this->product_category_id !== $categoryId) {
            return 0;
        }
        if ($this->product_item_id && $this->product_item_id !== $itemId) {
            return 0;
        }

        $points = 0;

        switch ($this->rule_type) {
            case 'purchase_based':
                if ($this->amount_per_point > 0) {
                    $points = floor($purchaseAmount / $this->amount_per_point);
                }
                break;

            case 'birthday_bonus':
            case 'referral_bonus':
                $points = $this->points_earned ?? 0;
                break;

            default:
                $points = $this->points_earned ?? 0;
                break;
        }

        return $points;
    }

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class, 'loyalty_program_id');
    }

    public function rewards()
    {
        return $this->hasMany(LoyaltyReward::class);
    }

    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function productItem(): BelongsTo
    {
        return $this->belongsTo(ProductItem::class);
    }
}