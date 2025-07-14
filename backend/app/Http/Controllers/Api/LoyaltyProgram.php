<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoyaltyProgramResource;
use App\Models\LoyaltyReward;
use Illuminate\Http\Request;

class LoyaltyProgram extends Controller
{

    public function show(LoyaltyProgramResource $loyaltyProgram){
        return new LoyaltyProgramResource($loyaltyProgram);
        
    }
    public function store(){
        
    }

    public function update(){
        
    }
    public function destroy(){
        
    }
}
