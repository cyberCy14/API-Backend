<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LoyaltyReward;
use Illuminate\Support\Facades\Validator;

class RewardsController extends Controller
{
    // List all vouchers
    public function index()
    {
        $vouchers = LoyaltyReward::all();
        return response()->json($vouchers);
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

        $voucher = LoyaltyReward::create($request->all());

        return response()->json($voucher, 201);
    }

    // Show a single voucher
    public function show($id)
    {
        $voucher = LoyaltyReward::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        return response()->json($voucher);
    }

    // Update a voucher
    public function update(Request $request, $id)
    {
        $voucher = LoyaltyReward::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        $voucher->update($request->all());

        return response()->json($voucher);
    }

    // Delete a voucher
    public function destroy($id)
    {
        $voucher = LoyaltyReward::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        $voucher->delete();

        return response()->json(['message' => 'Voucher deleted']);
    }
}
