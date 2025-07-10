<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RewardsResource;
use App\Models\Rewards; // Your plural model
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests\LoyaltyRewardsRequest;

class LoyaltyRewardsController extends Controller
{
    /**
     * Display a listing of the rewards.
     */
    public function index()
    {

        $reward = Rewards::get();

        if ($reward->count() > 0){
            return RewardsResource::collection($reward);
        }
        else{
            return response()->json(['message' => 'Empty'], status:200);
        }
        
    }

    /**
     * Store a newly created reward in storage.
     */
    public function store(LoyaltyRewardsRequest $request)
    {
        $reward = Rewards::create($request->validated());

        $reward = Rewards::create([ 
            'loyalty_program_id' => $request->loyalty_program_id,
            'reward_name' => $request->reward_name,
            'reward_type' => $request->reward_type,
            'point_cost' => $request->point_cost,
            'discount_value' => $request->discount_value,
            'discount_percentage' => $request->discount_percentage,
            'item_id' => $request->item_id,
            'voucher_code' => $request->voucher_code,
            'is_active' => $request->is_active,
        ]);

        return new RewardsResource($reward);
    }

    // Show a single voucher
    public function show(Rewards $reward)
    {
            return new RewardsResource($reward);
    }

    // Update a voucher
    public function update(LoyaltyRewardsRequest $request, Rewards $reward)
    {
        $reward = Rewards::create($request->validated());

        $reward->update([
            'reward_name' => $request->reward_name,
            'reward_type' => $request->reward_type,
            'point_cost' => $request->point_cost,
            'item_id' => $request->item_id,
        ]);
        return new RewardsResource($reward);
    }

    // Delete a voucher
    public function destroy(Rewards $reward)
    {
        $reward->delete();
        return response()->json(['message' => $reward]);

    }
}