<?php
namespace App\Filament\Pages\Concerns;

use App\Models\LoyaltyProgram;
use App\Services\LoyaltyService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait HandlesQrScanning
{
    // QR scanning properties
    public ?array $parsedQrData = null;
    public bool $showScanResult = false;
    public ?string $scannedCustomerName = null;
    public ?string $pendingQrData = null;

    public function processScannedQr(?string $qrData = null): void
    {
        try {
            // Use provided QR data or pending data
            $dataToProcess = $qrData ?? $this->pendingQrData;
            
            if (empty($dataToProcess)) {
                Notification::make()
                    ->title('No QR Data')
                    ->body('No QR code data to process.')
                    ->warning()
                    ->send();
                return;
            }

            Log::info('Processing scanned QR', ['qr_data' => $dataToProcess]);

            // Parse QR data - expecting JSON format
            $decodedData = $this->decodeQrData($dataToProcess);
            
            if (!$decodedData) {
                Notification::make()
                    ->title('Invalid QR Code')
                    ->body('Unable to parse QR code data. Please scan a valid customer QR code.')
                    ->danger()
                    ->send();
                $this->pendingQrData = null;
                return;
            }

            // Store the original parsed data for display
            $this->parsedQrData = $decodedData;

            // Normalize the field names for database lookup (support both formats)
            $normalizedData = $this->normalizeQrData($decodedData);

            // Validate required fields in QR data
            if (empty($normalizedData['customer_id']) && empty($normalizedData['customer_email'])) {
                Notification::make()
                    ->title('Invalid QR Code')
                    ->body('QR code must contain customer identification (id or email).')
                    ->danger()
                    ->send();
                $this->parsedQrData = null;
                $this->pendingQrData = null;
                return;
            }

            // CRITICAL: Verify customer exists in database
            $customer = $this->verifyCustomerInDatabase($normalizedData);
            
            if (!$customer) {
                Notification::make()
                    ->title('Customer Not Found')
                    ->body('The scanned customer does not exist in the system. Cannot proceed.')
                    ->danger()
                    ->persistent()
                    ->send();
                $this->parsedQrData = null;
                $this->pendingQrData = null;
                return;
            }

            // Additional validation: if both ID and email provided, verify they match
            if (!empty($normalizedData['customer_id']) && !empty($normalizedData['customer_email'])) {
                if ($customer->id !== $normalizedData['customer_id'] || $customer->email !== $normalizedData['customer_email']) {
                    Notification::make()
                        ->title('Customer Mismatch')
                        ->body('The customer ID and email in the QR code do not match our records.')
                        ->danger()
                        ->persistent()
                        ->send();
                    
                    Log::warning('Customer data mismatch', [
                        'qr_id' => $normalizedData['customer_id'],
                        'qr_email' => $normalizedData['customer_email'],
                        'db_id' => $customer->id,
                        'db_email' => $customer->email
                    ]);
                    
                    $this->parsedQrData = null;
                    $this->pendingQrData = null;
                    return;
                }
            }

            // Store results
            $this->showScanResult = true;
            $this->scannedCustomerName = $customer->name ?? $customer->email;

            // Auto-fill form with VERIFIED customer data from database
            $this->data['customer_id'] = $customer->id;
            $this->data['customer_email'] = $customer->email;
            $this->customerEmailDisplay = $customer->email;

            // Clear pending data
            $this->pendingQrData = null;

            // Show success notification with customer name
            Notification::make()
                ->title('Customer Verified Successfully!')
                ->body("Customer: {$customer->name} ({$customer->email})")
                ->success()
                ->send();

            Log::info('QR code processed and customer verified', [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'qr_contained' => $decodedData
            ]);

        } catch (\Exception $e) {
            Log::error('QR scanning failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Scanning Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
            
            $this->parsedQrData = null;
            $this->pendingQrData = null;
        }
    }

    /**
     * Verify customer exists in database and return the customer record
     */
    private function verifyCustomerInDatabase(array $normalizedData): ?User
    {
        $customer = null;

        // Try to find by ID first
        if (!empty($normalizedData['customer_id'])) {
            $customer = User::where('id', $normalizedData['customer_id'])->first();
        }

        // If not found by ID or ID not provided, try email
        if (!$customer && !empty($normalizedData['customer_email'])) {
            $customer = User::where('email', $normalizedData['customer_email'])->first();
        }

        return $customer;
    }

    public function creditPointsFromScan(): void
    {
        try {
            // Validate fields
            $validationRules = [
                'data.company_id' => 'required',
                'data.loyalty_program_id' => 'required',
                'data.purchase_amount' => 'required|numeric|min:0.01',
            ];
    
            // Require at least one customer identifier
            if (empty($this->data['customer_id']) && empty($this->data['customer_email'])) {
                Notification::make()
                    ->title('Customer Identifier Required')
                    ->body('Please scan customer QR code first')
                    ->danger()
                    ->send();
                return;
            }
    
            // Add validation for the provided identifier - FIXED: removed string requirement
            if (!empty($this->data['customer_id'])) {
                $validationRules['data.customer_id'] = 'required'; // Accept any type (string or int)
            }
            if (!empty($this->data['customer_email'])) {
                $validationRules['data.customer_email'] = 'required|email';
            }
    
            $this->validate($validationRules);
    
            // Additional security check
            if (!$this->validateCompanyAccess($this->data['company_id'])) {
                return;
            }
    
            // DOUBLE CHECK: Verify customer still exists before crediting
            if (!$this->validateCustomerExists($this->data['customer_id'] ?? null, $this->data['customer_email'] ?? null)) {
                Notification::make()
                    ->title('Security Check Failed')
                    ->body('Customer verification failed. Please scan QR code again.')
                    ->danger()
                    ->send();
                return;
            }
    
            // Verify the customer data hasn't been tampered with
            $customer = User::where('id', $this->data['customer_id'])
                ->where('email', $this->data['customer_email'])
                ->first();
    
            if (!$customer) {
                Notification::make()
                    ->title('Security Alert')
                    ->body('Customer ID and email do not match. Transaction blocked.')
                    ->danger()
                    ->persistent()
                    ->send();
                
                Log::warning('Attempted credit with mismatched customer data', [
                    'customer_id' => $this->data['customer_id'],
                    'customer_email' => $this->data['customer_email']
                ]);
                
                return;
            }
    
            // Check if points were calculated
            if (empty($this->calculatedPoints)) {
                Notification::make()
                    ->title('Error')
                    ->body('Please calculate points first')
                    ->danger()
                    ->send();
                return;
            }
    
            $totalPoints = (int) str_replace([' points', ','], '', $this->calculatedPoints);
    
            // Use the service to create the transaction
            $loyaltyService = app(LoyaltyService::class);
    
            $transactionData = [
                'customer_id' => $customer->id,
                'customer_email' => $customer->email,
                'company_id' => $this->data['company_id'],
                'loyalty_program_id' => $this->data['loyalty_program_id'],
                'points_earned' => $totalPoints,
                'purchase_amount' => $this->data['purchase_amount'],
                'rule_breakdown' => $this->ruleBreakdown,
                'created_by' => Auth::id(),
            ];
    
            $customerPoint = $loyaltyService->createEarningTransaction($transactionData);
    
            // Immediately confirm the transaction (since customer is present with QR)
            $confirmedTransaction = $loyaltyService->confirmEarning($customerPoint->transaction_id);
    
            if ($confirmedTransaction) {
                Log::info('Points credited successfully', [
                    'transaction_id' => $confirmedTransaction->transaction_id,
                    'customer_id' => $customer->id,
                    'customer_email' => $customer->email,
                    'points' => $totalPoints,
                    'status' => $confirmedTransaction->status
                ]);
    
                Notification::make()
                    ->title('Points Credited Successfully!')
                    ->body("Successfully credited {$totalPoints} points to {$customer->name} ({$customer->email}). Transaction ID: {$confirmedTransaction->transaction_id}")
                    ->success()
                    ->persistent()
                    ->send();
    
                // Reset form after successful credit
                $this->resetCalculation();
                $this->resetScanData();
            } else {
                throw new \Exception('Failed to confirm transaction');
            }
    
        } catch (\Exception $e) {
            Log::error('Point crediting failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data ?? []
            ]);
    
            Notification::make()
                ->title('Point Crediting Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }
    
    private function normalizeQrData(array $data): array
    {
        $normalized = [];

        // Handle customer ID (support both 'id' and 'customer_id')
        if (isset($data['id'])) {
            $normalized['customer_id'] = $data['id'];
        } elseif (isset($data['customer_id'])) {
            $normalized['customer_id'] = $data['customer_id'];
        }

        // Handle customer email (support both 'email' and 'customer_email')
        if (isset($data['email'])) {
            $normalized['customer_email'] = $data['email'];
        } elseif (isset($data['customer_email'])) {
            $normalized['customer_email'] = $data['customer_email'];
        }
        return $normalized;
    }

    private function decodeQrData(string $qrData): ?array
    {
        // Try to decode as JSON first
        $decoded = json_decode($qrData, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try to parse as URL query string
        if (filter_var($qrData, FILTER_VALIDATE_URL)) {
            $urlParts = parse_url($qrData);
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $queryParams);
                
                // Check for payload parameter
                if (isset($queryParams['payload'])) {
                    $payloadDecoded = base64_decode($queryParams['payload']);
                    $payloadJson = json_decode($payloadDecoded, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($payloadJson)) {
                        return $payloadJson;
                    }
                }
                
                return $queryParams;
            }
        }

        // Try base64 decode
        $base64Decoded = base64_decode($qrData, true);
        if ($base64Decoded !== false) {
            $decoded = json_decode($base64Decoded, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function resetScanData(): void
    {
        $this->parsedQrData = null;
        $this->showScanResult = false;
        $this->scannedCustomerName = null;
        $this->pendingQrData = null;
    }
}