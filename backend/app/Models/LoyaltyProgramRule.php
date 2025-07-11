<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgramRule extends Model
{
    protected $table = 'loyaltyProgramRules';
    protected $fillable = [
        'loyalty_program_id',
        'rule_name',
        'rule_type',
        'points_earned',
        'amount_per_point',
        'min_purchase_amount',
        'product_category_id',
        'product_item_id',
        'is_active',
        'active_from_date',
        'active_to_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'active_from_date' => 'date',
        'active_to_date' => 'date',
        'points_earned' => 'integer',
        'amount_per_point' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'usage_limit' => 'integer',
    ];

    public function loyaltyProgram(): BelongsTo
    {
        return $this->belongsTo(LoyaltyProgram::class);
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
