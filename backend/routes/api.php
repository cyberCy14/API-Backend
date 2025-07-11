<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LoyaltyRuleController;
use App\Http\Controllers\Api\LoyaltyRewardsController;

Route::apiResource('company', CompanyController::class);
Route::apiResource('reward', LoyaltyRewardsController::class);

Route::apiResource('loyalty-rules', LoyaltyRuleController::class);

Route::middleware('auth:sanctum')->group(function(){
        Route::post('register',[AuthController::class, 'register']);
        Route::post('login',[AuthController::class, 'login']);
        Route::post('logout',[AuthController::class, 'logout']);
});