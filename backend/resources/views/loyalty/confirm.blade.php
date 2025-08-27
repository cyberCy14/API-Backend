<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Loyalty Points Confirmation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-2xl shadow-xl p-6">
            <!-- Header -->
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    @if($transaction_type === 'earning')
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    @else
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                        </svg>
                    @endif
                </div>
                <h1 class="text-2xl font-bold text-gray-800">
                    @if($transaction_type === 'earning')
                        Earn Points
                    @else
                        Redeem Points
                    @endif
                </h1>
            </div>

            <!-- Customer Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-gray-700 mb-2">Customer Information</h2>
                <p class="text-gray-600">Name: <span class="font-medium">{{ $customer->name ?? 'N/A' }}</span></p>
                <p class="text-gray-600">Email: <span class="font-medium">{{ $customer->email ?? 'N/A' }}</span></p>
                <p class="text-gray-600">Current Points: 
                    <span class="font-medium text-blue-600" id="currentPoints">{{ $customer->total_points ?? 0 }}</span>
                </p>
            </div>

            <!-- Transaction Details -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 mb-6">
                <h2 class="font-semibold text-gray-700 mb-3">Transaction Details</h2>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Points:</span>
                    <span class="text-2xl font-bold text-blue-600">{{ $points }}</span>
                </div>
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600">Type:</span>
                    <span class="font-medium capitalize {{ $transaction_type === 'earning' ? 'text-green-600' : 'text-orange-600' }}">
                        {{ $transaction_type }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">Transaction ID:</span>
                    <span class="font-mono text-sm text-gray-500">{{ $transaction->transaction_id }}</span>
                </div>
            </div>

            <!-- Real New Balance -->
            <div class="{{ $transaction_type === 'earning' ? 'bg-green-50 border border-green-200' : 'bg-orange-50 border border-orange-200' }} rounded-lg p-4 mb-6">
                <p class="{{ $transaction_type === 'earning' ? 'text-green-800' : 'text-orange-800' }} text-center">
                    <span class="font-medium">New Balance: 
                        <span id="newBalance">{{ $customer->total_points ?? 0 }}</span> points
                    </span>
                </p>
            </div>

            <!-- Confirmation Buttons -->
            <div class="space-y-3">
                <button id="confirmBtn" 
                        class="w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-blue-600 hover:to-indigo-700 transition-all duration-200 transform hover:scale-105 shadow-lg">
                    @if($transaction_type === 'earning')
                        Confirm & Credit Points
                    @else
                        Confirm & Redeem Points
                    @endif
                </button>
                
                <button onclick="window.history.back()" 
                        class="w-full bg-gray-300 text-gray-700 font-semibold py-3 px-6 rounded-lg hover:bg-gray-400 transition-colors duration-200">
                    Cancel
                </button>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="hidden text-center py-4">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <p class="mt-2 text-gray-600">Processing...</p>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#confirmBtn').click(function() {
                const button = $(this);
                const loadingState = $('#loadingState');
                const transactionType = '{{ $transaction_type }}';
                const transactionId = '{{ $transaction->transaction_id }}';
                
                // Show loading state
                button.prop('disabled', true).addClass('opacity-50');
                loadingState.removeClass('hidden');
                
                // Decide endpoint
                const url = transactionType === 'earning' 
                    ? `/loyalty/confirm-earning/${transactionId}`
                    : `/loyalty/confirm-redemption/${transactionId}`;
                
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                
                $.post(url)
                    .done(function(response) {
                        if (response.success && response.redirect_url) {
                            loadingState.addClass('hidden');
                            button.removeClass('opacity-50').addClass('bg-green-500')
                                  .text('Success! Redirecting...').prop('disabled', true);

                            // ✅ Update balance in the UI
                            if (response.new_balance !== undefined) {
                                $('#newBalance').text(response.new_balance);
                                $('#currentPoints').text(response.new_balance);
                            }

                            setTimeout(function() {
                                window.location.href = response.redirect_url;
                            }, 1500);
                        } else {
                            throw new Error(response.message || 'Unknown error occurred');
                        }
                    })
                    .fail(function(xhr) {
                        loadingState.addClass('hidden');
                        button.prop('disabled', false).removeClass('opacity-50');
                        
                        let errorMessage = 'An error occurred. Please try again.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        alert(errorMessage);
                    });
            });
        });
    </script>
</body>
</html>
