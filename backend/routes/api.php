<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoyaltyRewardsController;
use App\Http\Controllers\Api\CompanyController;


Route::apiResource('company', CompanyController::class);
Route::apiResource('reward', LoyaltyRewardsController::class);
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});



