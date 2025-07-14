<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoyaltyRuleController;
use App\Http\Controllers\Api\LoyaltyRewardsController;
use App\Http\Controllers\Api\CompanyController;

use App\Http\Controllers\Api\UserController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::apiResource('company', CompanyController::class);
Route::apiResource('loyalty-rules', LoyaltyRuleController::class);
Route::apiResource('users', UserController::class);



Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user()->tokens;
});
