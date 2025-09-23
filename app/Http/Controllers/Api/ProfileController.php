<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Profile;
use App\Models\User;


class ProfileController extends Controller
{

    public function show()
    {
        $user = Auth::user();
        $profile = $user->profile;

        return response()->json([
            'user' => $user,
            'profile' => $profile,
        ]);
    }


    public function update(Request $request)
    {
         $user = $request->user();
         

        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'place' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
            'gender' => 'nullable|string|in:Male,Female,Other',
            'image' => 'nullable|image|max:10240
            
            3',
            // 'image' => $request->hasFile('image') ? 'image|max:10240' : 'nullable',

        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('profiles', 'public');
            $data['image'] = $path;
        }

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
            'profile' => $profile,
        ]);


        if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            
    }
}
