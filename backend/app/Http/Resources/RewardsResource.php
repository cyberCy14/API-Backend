<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
            //return parent::toArray($request);

        return[
            'loyalty_program_id' => 'required',
            'reward_name' => 'required|string|max:255',
            'reward_type' => 'required|string',
            'point_cost' => 'required|numeric|min:0',
            'discount_value' => 'nullable|numeric',
            'discount_percentage' => 'nullable|numeric',
            'item_id' => 'required|integer',
            'voucher_code' => 'nullable|string|unique:loyaltyRewards,voucher_code',
            'is_active' => 'boolean',
            'max_redemption_rate' => 'nullable|integer',
            'expiration_days' => 'nullable|integer'
        ];
        
    }
}
