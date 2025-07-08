<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rewards extends Model
{
    use HasFactory;

    protected $table = 'loyaltyRewards';

    protected $fillable = [
        //'loyalty_program_id',
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
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'point_cost' => 'float',
        'discount_value' => 'float',
        'discount_percentage' => 'float',
    ];

    // Relationships
    // public function loyaltyProgram()
    // {
    //     return $this->belongsTo(LoyaltyProgram::class);
    // }

    // public function item()
    // {
    //     return $this->belongsTo(Item::class);
    // }
}
