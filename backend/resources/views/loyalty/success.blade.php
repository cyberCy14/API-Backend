<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-2xl shadow-xl p-6 text-center">
            <!-- Success Icon -->
            <div class="w-20 h-20 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <!-- Success Message -->
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                @if($transaction->transaction_type === 'earning')
                    Points Credited!
                @else
                    Points Redeemed!
                @endif
            </h1>
            
            <p class="text-gray-600 mb-6">
                Your transaction has been processed successfully.
            </p>

            <!-- Transaction Summary -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-gray-700 mb-3">Transaction Summary</h2>
                
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Customer:</span>
                        <span class="font-medium">{{$customer->customer_email}}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Points:</span>
                        <span class="font-bold text-lg {{ $transaction->transaction_type === 'earning' ? 'text-green-600' : 'text-orange-600' }}">
                            @if($transaction->transaction_type === 'earning')
                                +{{$customer->points_earned}}
                            @else
                                -{{ $transaction->points }}
                            @endif
                        </span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Status:</span>
                        <span class="font-medium text-green-600 capitalize">{{ $transaction->status }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">New Balance:</span>
                        <span class="font-bold text-blue-600">{{ $customerTotalPoints ?? 0 }} points</span>
                    </div>
                </div>
            </div>

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

            <!-- Action Button -->
            <button onclick="window.close()" 
                    class="w-full bg-gradient-to-r from-green-500 to-emerald-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-green-600 hover:to-emerald-700 transition-all duration-200">
                Close
            </button>
        </div>
    </div>
</body>
</html>