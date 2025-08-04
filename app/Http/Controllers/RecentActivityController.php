<?php

namespace App\Http\Controllers;

use App\Events\RecentActivityCreated;
use App\Models\RecentActivity;
use Illuminate\Http\Request;

class RecentActivityController extends Controller
{
    public function index(Request $request){
          $user = $request->user();

        $activities = RecentActivity::where('user_id', $user->id) 
                                    ->orderBy('created_at', 'desc') 
                                    ->limit(10) 
                                    ->get();

        return response()->json([
            'success' => true,
            'data' => $activities,
        ]);
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

        broadcast(new RecentActivityCreated($activity));

        return response()->json(['data' => $activity], 201);
    }
}
