<?php

namespace App\Http\Controllers;

use App\Models\RecentActivity;
use Illuminate\Http\Request;

class RecentActivityController extends Controller
{
    public function index(Request $request){
        $activities = RecentActivity::where('user_id', $request->user()->id)
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json(['data' => $activities]);
    }

    public function store(Request $request){
        $validated = $request->validate([
            'type' => 'required|string',
            'points' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        $activity = RecentActivity::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'points' => $validated['points'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json(['data' => $activity], 201);
    }
}
