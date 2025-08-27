<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Already Processed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-yellow-50 to-orange-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-2xl shadow-xl p-6 text-center">
            <!-- Warning Icon -->
            <div class="w-20 h-20 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>

            <!-- Message -->
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                Already Processed
            </h1>
            
            <p class="text-gray-600 mb-6">
                {{ $message ?? 'This transaction has already been processed.' }}
            </p>

            <!-- Transaction Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-gray-700 mb-3">Transaction Details</h2>
                
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Points:</span>
                        <span class="font-bold {{ $transaction->transaction_type === 'earning' ? 'text-green-600' : 'text-orange-600' }}">
                            @if($transaction->transaction_type === 'earning')
                                +{{ $transaction->points }}
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
                        <span class="text-gray-600">Processed:</span>
                        <span class="text-sm">{{ $transaction->updated_at->format('M d, Y') }}</span>
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

            <!-- Action Button -->
            <button onclick="window.close()" 
                    class="w-full bg-gradient-to-r from-gray-500 to-gray-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all duration-200">
                Close
            </button>
        </div>
    </div>
</body>
</html>