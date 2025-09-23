<?php

use App\Http\Controllers\CompanyTransactionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\LoyaltyRuleController;
use App\Http\Controllers\Api\LoyaltyRewardsController;
use App\Http\Controllers\Api\LoyaltyProgramController;
use App\Http\Controllers\LoyaltyWebhookController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\RecentActivityController;
use App\Http\Controllers\Api\LoyaltyTransactionController;
use App\Http\Controllers\CustomerCompanyBalancesController;
use App\Http\Controllers\CustomerPointsController;


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {


    Route::get('user', function (Request $request) {
        return response()->json([
            'user' => $request->user(),
            'token' => $request->bearerToken(),
        ]);
    });

    Route::get('/user', [UserController::class, 'show']);
    Route::post('/user', [UserController::class, 'update']);
    Route::get('/loyalty', [UserController::class, 'getLoyaltyInfo']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);

     Route::apiResource('companies', CompanyController::class);
    Route::apiResource('rewards', LoyaltyRewardsController::class);
    Route::apiResource('loyalty-rules', LoyaltyRuleController::class);
    Route::apiResource('loyalty-programs', LoyaltyRuleController::class);
    Route::apiResource('loyalty-transaction', LoyaltyTransactionController::class);
    Route::apiResource('recent-activities', RecentActivityController::class)->only(['index', 'store']);


    Route::post('logout', [AuthController::class, 'logout']);




Route::prefix('loyalty')->group(function () {
    Route::post('confirm-earning/{transactionId}', [LoyaltyController::class, 'confirmEarning']);
    Route::post('confirm-redemption/{transactionId}', [LoyaltyController::class, 'confirmRedemption']);

    Route::get('customer-company-balances', [CustomerCompanyBalancesController::class, 'index' ]);

    Route::post('customer-company-balances', [CustomerCompanyBalancesController::class, 'store']);
    Route::get('customer-points/{customer_id}', [CustomerPointsController::class, 'index']);
});



});
