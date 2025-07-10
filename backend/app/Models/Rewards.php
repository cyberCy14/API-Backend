<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rewards extends Model
{
    use HasFactory;

    protected $table = 'loyaltyRewards';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'loyalty_program_id', // <-- FIX: Added this so you can create rewards for a program.
        'reward_name',
        'reward_type',
        'point_cost',
        'discount_value',
        'discount_percentage',
        'item_id',
        'voucher_code',
        'is_active',
        'max_redemption_rate',
        'expiration_days',
        'description', // Also added description just in case
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'point_cost' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
    ];

    // Relationships - These are fine
    public function loyaltyProgram()
    {
        // Assuming you have a LoyaltyProgram model
        return $this->belongsTo(LoyaltyProgram::class);
    }

    public function item()
    {
        // Assuming you have an Item model
        return $this->belongsTo(Item::class);
    }
}
