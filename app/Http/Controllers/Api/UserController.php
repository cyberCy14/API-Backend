<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Show the authenticated user (with profile + loyalty)
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile');

        return response()->json([
            'user' => $user,
            'profile' => $user->profile,
        ]);
    }

    /**
     * Update the authenticated user's account (basic info like name/email)
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $user->id,
        ]);

        $user->update($data);

        return response()->json([
            'user'    => $user,
            'message' => 'User updated successfully'
        ]);
    }

    /**
     * Get loyalty info for the authenticated user
     */
    // public function getLoyaltyInfo(Request $request)
    // {
    //     $user = $request->user();

    //     return response()->json([
    //         'total_points'          => $user->total_points,
    //         'next_reward_threshold' => 1000, //  depends on the rules
    //         'invite_code'           => $user->invite_code,
    //     ]);
    // }
}