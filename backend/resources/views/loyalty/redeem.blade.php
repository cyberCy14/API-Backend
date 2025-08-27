<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redemption Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-orange-50 to-red-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-2xl shadow-xl p-6 text-center">
            <!-- Success Icon -->
            <div class="w-20 h-20 bg-gradient-to-r from-orange-500 to-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                </svg>
            </div>

            <!-- Success Message -->
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                Points Redeemed!
            </h1>
            <p class="text-gray-600 mb-6">
                Your redemption has been processed successfully.
            </p>

            <!-- Transaction Summary -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-gray-700 mb-3">Redemption Summary</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Customer:</span>
                        <span class="font-medium">{{ $customer->customer_email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Points Redeemed:</span>
                        <span class="font-bold text-lg text-red-600">
                            -{{ $pointsRedeemed ?? abs($transaction->points_earned) }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium text-green-600 capitalize">{{ $transaction->status }}</span>
                    </div>
                    <div class="flex justify-between border-t pt-2 mt-3">
                        <span class="text-gray-600 font-medium">Remaining Balance:</span>
                        <span class="font-bold text-lg text-blue-600">{{ $customerTotalPoints ?? 0 }} points</span>
                    </div>
                </div>
            </div>

            <!-- Reward Information (Optional) -->
            @if(isset($reward_name) || isset($reward_description))
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-3 mb-6">
                <h3 class="font-medium text-orange-800 mb-1">Reward Claimed</h3>
                @if(isset($reward_name))
                    <p class="text-sm text-orange-700 font-medium">{{ $reward_name }}</p>
                @endif
                @if(isset($reward_description))
                    <p class="text-xs text-orange-600 mt-1">{{ $reward_description }}</p>
                @endif
            </div>
            @endif

            <!-- Transaction ID -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-6">
                <p class="text-sm text-blue-800">
                    <span class="font-medium">Transaction ID:</span><br>
                    <span class="font-mono text-xs">{{ $transaction->transaction_id }}</span>
                </p>
            </div>

            <!-- Date & Time -->
            <p class="text-sm text-gray-500 mb-6">
                Processed on {{ $transaction->updated_at->format('M d, Y \a\t g:i A') }}
            </p>

            <!-- Success Message -->
            @if(isset($message))
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-6">
                <p class="text-sm text-green-800">{{ $message }}</p>
            </div>
            @endif

            <!-- Action Button -->
            <button onclick="window.close()"
                class="w-full bg-gradient-to-r from-orange-500 to-red-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-orange-600 hover:to-red-700 transition-all duration-200">
                Close
            </button>
        </div>
    </div>
</body>
</html>