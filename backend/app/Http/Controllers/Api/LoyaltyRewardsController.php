<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Resources\RewardsResource;
use Illuminate\Http\Request;
use App\Models\Rewards;
use Illuminate\Support\Facades\Validator;


class LoyaltyRewardsController extends Controller // Now, this line will work correctly
{
    // List all rewards
    public function index()
    {
        $rewards = Rewards::get();

        if ($rewards->count() > 0){
            return RewardsResource::collection($rewards);
        }
        else{
            return response()->json(['message' => 'Empty'], status:200);
        }
        
        
    }

    // Create a new voucher
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $voucher = Rewards::create($request->all());

        return response()->json($voucher, 201);
    }

    // Show a single voucher
    public function show($id)
    {
        $voucher = Rewards::find($id);

        if (!$voucher) {
            return new RewardsResource($company);
        }

        return response()->json($voucher);
    }

    // Update a voucher
    public function update(Request $request, Rewards $rewards)
    {
        $voucher = Rewards::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        $voucher->update($request->all());

        return response()->json($voucher);
    }

    // Delete a voucher
    public function destroy(Rewards $rewards)
    {
        $voucher = Rewards::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        $voucher->delete();

        return response()->json(['message' => 'Voucher deleted']);
    }
}