<?php

namespace App\Http\Requests;

use App\Rules\DiscountPercentageRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\ValidRewardFields;

class LoyaltyRewardsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loyalty_program_rule_id' => 'required|exists:loyaltyProgramRule,id',
            'reward_name' => 'required|string|max:255',
            'reward_type' => ['required', 'string'],
            new ValidRewardFields(),
            'point_cost' => 'required|numeric|min:0',
            'discount_value' => 'nullable|numeric',
            'discount_percentage' => ['nullable','numeric', new DiscountPercentageRule],
            'item_id' => 'required|integer',
            'voucher_code' => 'nullable|string|unique:loyaltyRewards,voucher_code',
            'is_active' => 'boolean',
            'max_redemption_rate' => 'nullable|integer',
            'expiration_days' => 'nullable|integer'
        ];
    }
}
