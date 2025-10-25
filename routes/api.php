<?php

use App\Http\Controllers\CompanyTransactionController;
use Illuminate\Support\Facades\Route;
// use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\LoyaltyRuleController;
use App\Http\Controllers\LoyaltyRewardController;
use App\Http\Controllers\LoyaltyProgramController;
use App\Http\Controllers\LoyaltyWebhookController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\CustomerCompanyBalancesController;
use App\Http\Controllers\CustomerPointsController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\Api\LoyaltyConfirmController;


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
    Route::apiResource('loyalty-rewards', LoyaltyRewardController::class);
    Route::apiResource('loyalty-rules', LoyaltyRuleController::class);
    Route::apiResource('loyalty-programs', LoyaltyProgramController::class);
    Route::get('/rewards/company/{companyId}', [LoyaltyRewardController::class, 'byCompany']);
    Route::post('loyalty-rewards/{reward}/redeem', [LoyaltyRewardController::class, 'redeem']);
    Route::get('loyalty/customer-company-balance', [CustomerCompanyBalancesController::class, 'customerCompanyBalance']);

    Route::post('logout', [AuthController::class, 'logout']);




Route::prefix('loyalty')->group(function () {

    Route::get('customer-company-balances', [CustomerCompanyBalancesController::class, 'index' ]);

    Route::post('customer-company-balances', [CustomerCompanyBalancesController::class, 'store']);
    Route::get('customer-points/{customer_id}', [CustomerPointsController::class, 'index']);

    Route::post('confirm-redeem/{transactionId}', [LoyaltyConfirmController::class, 'confirmRedeem']);
    Route::post('confirm-redemption/{transactionId}', [LoyaltyConfirmController::class, 'confirmRedeem']);
    Route::post('confirm-earning/{transactionId}', [LoyaltyConfirmController::class, 'confirmEarning']);

    Route::post('cancel-redeem/{transactionId}', [LoyaltyConfirmController::class, 'cancelRedeem']);
    Route::post('cancel-redemption/{transactionId}', [LoyaltyConfirmController::class, 'cancelRedeem']);
    Route::post('cancel-earning/{transactionId}', [LoyaltyConfirmController::class, 'cancelEarning']);
    Route::get('tx/{transactionId}',[LoyaltyConfirmController::class, 'showTx']);
});

    //BUSINESS FINANCIAL REPORT

    Route::get('/financial-reports', [FinancialReportController::class, 'index']);
    Route::post('/financial-reports', [FinancialReportController::class, 'store']);
    Route::get('/financial-reports/{financialReport}', [FinancialReportController::class, 'show']);


});
