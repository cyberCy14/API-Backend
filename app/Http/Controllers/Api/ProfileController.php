<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
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
    $user = Auth::user();

    $profile = $user->profile ?? new Profile(['user_id' => $user->id]);

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'place' => 'nullable|string|max:255',
        'dob' => 'nullable|date',
        'gender' => 'nullable|string|max:50',
        'image' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
    ]);

    $profile->fill($validated);

    if ($request->hasFile('image')) {
        // delete old image if exists
        if ($profile->image && \Storage::disk('public')->exists($profile->image)) {
            \Storage::disk('public')->delete($profile->image);
        }

        // store new image
        $imagePath = $request->file('image')->store('profiles', 'public');
        $profile->image = $imagePath;
    }

    $profile->save();

    if ($profile->image) {
    $profile->image = asset('storage/' . $profile->image);
}

    return response()->json([
        'message' => 'Profile updated successfully',
        'profile' => $profile,
        'user' => $user,
    ]);
}

}
