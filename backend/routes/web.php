<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Models\CustomerPoint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

Route::redirect('/dashboard', '/admin');

require __DIR__ . '/auth.php';

Route::get('loyalty/confirm-earning/{transactionId}', function (string $transactionId) {
    Log::info("Confirming earning for transaction ID: $transactionId");

    $customerPoint = CustomerPoint::where('transaction_id', $transactionId)
        ->where('transaction_type', 'earning')
        ->first();

    if (!$customerPoint) {
        Log::warning("Earning transaction not found for ID: $transactionId");
        return response()->json(['message' => 'Earning transaction not found'], 404);
    }

    if ($customerPoint->status === 'credited') {
        Log::info("Earning already credited for transaction ID: $transactionId");
        return response()->json(['message' => 'Earning already credited'], 200);
    }

    if ($customerPoint->creditPoints()) {
        Log::info("Earning credited successfully for transaction ID: $transactionId");

        $customerPoint->update(['credited_at' => now()]);

        $customerTotalPoints = CustomerPoint::where('customer_email', $customerPoint->customer_email)
            ->where('company_id', $customerPoint->company_id)
            ->where('status', 'completed')
            ->sum('points_earned');

        return view('loyalty.success', [
            'transaction' => $customerPoint->fresh(),
            'customer' => $customerPoint->fresh(),
            'customerTotalPoints' => $customerTotalPoints,
            'message' => 'Points credited successfully!',
            'redirect_url' => route('loyalty.success', $transactionId)
        ]);
    }

    Log::error("Failed to credit earning for transaction ID: $transactionId");
    return response()->json(['message' => 'Failed to credit earning'], 500);
})->name('loyalty.success');

Route::get('loyalty/confirm-redemption/{transactionId}', function (string $transactionId) {
    Log::info("Confirming redemption for transaction ID: $transactionId");

    $customerPoint = CustomerPoint::where('transaction_id', $transactionId)
        ->where('transaction_type', 'redemption')
        ->first();

    if (!$customerPoint) {
        Log::warning("Redemption transaction not found for ID: $transactionId");
        return response()->json(['message' => 'Redemption transaction not found'], 404);
    }

    if ($customerPoint->status === 'completed') {
        Log::info("Redemption already completed for transaction ID: $transactionId");
        return response()->json(['message' => 'Redemption already completed'], 200);
    }

    $customerTotalPoints = CustomerPoint::where('customer_email', $customerPoint->customer_email)
        ->where('company_id', $customerPoint->company_id)
        ->where('status', 'completed')
        ->sum('points_earned');

    $pointsToRedeem = abs($customerPoint->points_earned);

    if ($customerTotalPoints < $pointsToRedeem) {
        Log::warning("Insufficient points for redemption. Customer has: $customerTotalPoints, trying to redeem: $pointsToRedeem");
        return view('loyalty.error', [
            'message' => 'Insufficient points for redemption',
            'customerTotalPoints' => $customerTotalPoints,
            'pointsRequested' => $pointsToRedeem
        ]);
    }

    if ($customerPoint->redeemPoints()) {
        Log::info("Points redeemed successfully for transaction ID: $transactionId");

        $customerPoint->update(['redeemed_at' => now()]);

        $customerTotalPointsAfter = CustomerPoint::where('customer_email', $customerPoint->customer_email)
            ->where('company_id', $customerPoint->company_id)
            ->where('status', 'completed')
            ->sum('points_earned');

        return view('loyalty.redeem', [
            'transaction' => $customerPoint->fresh(),
            'customer' => $customerPoint->fresh(),
            'customerTotalPoints' => $customerTotalPointsAfter,
            'pointsRedeemed' => $pointsToRedeem,
            'message' => 'Points redeemed successfully!',
            'redirect_url' => route('loyalty.redeem', $transactionId)
        ]);
    }

    Log::error("Failed to redeem points for transaction ID: $transactionId");
    return response()->json(['message' => 'Failed to redeem points'], 500);
})->name('loyalty.redeem');
