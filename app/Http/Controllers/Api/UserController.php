<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    // Get the authenticated user's profile
    public function show(Request $request)
    {
        $user = $request->user();
        return response()->json(['user' => $user]);
    }

    // Update the authenticated user's profile
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->only(['name', 'place', 'dob', 'gender']);

        // Validate input
        $validator = Validator::make($data, [
            'name' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string|max:32',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Handle image upload if present
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('profile_images', 'public');
            $data['image'] = $path;
            // Optionally, delete old image file here
        }

        $user->update($data);

        return response()->json(['user' => $user, 'message' => 'Profile updated successfully']);
    }


    public function getLoyaltyInfo(Request $request){
        $user = $request->user();

        return response()->json([
            'total_points' => $user->total_points,
            'next_reward_threshold' => 1000, //this should depend on the rules
            'invite_code' => $user->invite_code,
        ]);
    }

} 