<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyReward;
use App\Models\CustomerPoint;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class LoyaltyRewardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->query('company_id');

        $rewards = LoyaltyReward::with('loyaltyProgramRule.loyaltyProgram')
            ->when($companyId, function ($query) use ($companyId) {
                $query->whereHas('loyaltyProgramRule.loyaltyProgram', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            })
            ->get();

        return response()->json(['data' => $rewards]);
    }

//    public function redeem(Request $request, $rewardId)
// {
//     $customerId = $request->input('customer_id');

//     // ✅ Load relationships eagerly to avoid null access
//     $reward = LoyaltyReward::with('loyaltyProgramRule.loyaltyProgram')->findOrFail($rewardId);

//     // ✅ Correct relationship access (camelCase)
//     $companyId = optional($reward->loyaltyProgramRule->loyaltyProgram)->company_id;

//     if (!$companyId) {
//         return response()->json([
//             'message' => 'Company not found for this reward',
//             'debug' => [
//                 'reward_id' => $rewardId,
//                 'has_rule' => !is_null($reward->loyaltyProgramRule),
//                 'has_program' => !is_null(optional($reward->loyaltyProgramRule)->loyaltyProgram),
//             ]
//         ], 404);
//     }

//     // ✅ Use the latest recorded balance
//     $latestPointRecord = CustomerPoint::where('customer_id', $customerId)
//         ->where('company_id', $companyId)
//         ->latest('id')
//         ->first();

//     $totalBalance = $latestPointRecord ? $latestPointRecord->balance : 0;

//     if ($totalBalance < $reward->point_cost) {
//         return response()->json([
//             'message' => 'Insufficient points to redeem this reward',
//             'user_points_balance' => $totalBalance
//         ], 400);
//     }

//     $newBalance = $totalBalance - $reward->point_cost;

//     CustomerPoint::create([
//         'customer_id' => $customerId,
//         'company_id' => $companyId,
//         'points_earned' => -$reward->point_cost,
//         'balance' => $newBalance,
//         'transaction_type' => 'redemption',
//         'status' => 'completed',
//     ]);

//     return response()->json([
//         'message' => 'Reward redeemed successfully!',
//         'user_points_balance' => $newBalance,
//     ]);
// }


public function redeem(Request $request, $rewardId)
{
    $customerId = $request->input('customer_id');

    $reward = LoyaltyReward::with('loyaltyProgramRule.loyaltyProgram')->findOrFail($rewardId);

    $companyId = optional($reward->loyaltyProgramRule->loyaltyProgram)->company_id;

    if (!$companyId) {
        return response()->json([
            'message' => 'Company not found for this reward',
            'debug' => [
                'reward_id' => $rewardId,
                'has_rule' => !is_null($reward->loyaltyProgramRule),
                'has_program' => !is_null(optional($reward->loyaltyProgramRule)->loyaltyProgram),
            ]
        ], 404);
    }

    $latestPointRecord = CustomerPoint::where('customer_id', $customerId)
        ->where('company_id', $companyId)
        ->latest('id')
        ->first();

    $totalBalance = $latestPointRecord ? $latestPointRecord->balance : 0;

    if ($totalBalance < $reward->point_cost) {
        return response()->json([
            'message' => 'Insufficient points to redeem this reward',
            'user_points_balance' => $totalBalance
        ], 400);
    }

    $customer = \App\Models\User::find($customerId);

    $transactionId = (string) Str::uuid();

    $newBalance = $totalBalance - $reward->point_cost;

    CustomerPoint::create([
        'customer_id' => $customerId,
        'customer_email' => $customer ? $customer->email : null,
        'company_id' => $companyId,
        'loyalty_program_id' => optional($reward->loyaltyProgramRule->loyaltyProgram)->id,
        'transaction_id' => $transactionId,
        'points_earned' => -$reward->point_cost,
        'transaction_type' => 'redemption',
        'status' => 'completed',
        'balance' => $newBalance,
        'redemption_description' => $reward->reward_name,
        'transaction_date' => now(),
        'redeemed_at' => now(),
    ]);

    return response()->json([
        'message' => 'Reward redeemed successfully!',
        'user_points_balance' => $newBalance,
    ]);
}


    public function store(Request $request, $programId)
    {
        $validator = Validator::make($request->all(), [
            'reward_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'point_cost' => 'required|numeric|min:1',
            'reward_type' => 'required|in:discount,free_item,service,currency',
            'discount_value' => 'nullable|numeric',
            'discount_percentage' => 'nullable|numeric',
            'voucher_code' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reward = LoyaltyReward::create(array_merge(
            $request->all(), ['loyalty_program_rule_id' => $programId]
        ));

        return response()->json($reward, 201);
    }

    public function show($programId, $rewardId)
    {
        return LoyaltyReward::where('loyalty_program_rule_id', $programId)->findOrFail($rewardId);
    }

    public function update(Request $request, $programId, $rewardId)
    {
        $reward = LoyaltyReward::where('loyalty_program_rule_id', $programId)->findOrFail($rewardId);

        $validator = Validator::make($request->all(), [
            'reward_name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'point_cost' => 'sometimes|required|numeric|min:1',
            'reward_type' => 'sometimes|required|in:discount,free_item,service,currency',
            'discount_value' => 'nullable|numeric',
            'discount_percentage' => 'nullable|numeric',
            'voucher_code' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $reward->update($request->all());
        return response()->json($reward);
    }

    public function destroy($programId, $rewardId)
    {
        $reward = LoyaltyReward::where('loyalty_program_rule_id', $programId)->findOrFail($rewardId);
        $reward->delete();
        return response()->json(null, 204);
    }

    public function byCompany($companyId)
    {
        $rewards = LoyaltyReward::with('loyaltyProgramRule.loyaltyProgram')
            ->when($companyId, function ($query) use ($companyId) {
                $query->whereHas('loyaltyProgramRule.loyaltyProgram', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            })
            ->get();

        return response()->json(['data' => $rewards]);
    }
}
