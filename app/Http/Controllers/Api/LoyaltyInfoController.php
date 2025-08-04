<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoyaltyInfoController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'total_points' => $user->total_points,
            'invite_code' => $user->invite_code,
            'company_breakdown' => $user->loyaltyAccounts()->with('company:id,name')->get()->map(function ($acc) {
                return [
                    'company_id' => $acc->company_id,
                    'company_name' => $acc->company->name,
                    'points' => $acc->points,
                ];
            }),
        ]);
    }
}
