<?php
namespace App\Filament\Pages\Concerns;

use App\Models\CustomerPoint;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\Services\LoyaltyService;

trait HandlesPointRedemption
{
    // Redemption properties
    public ?string $redemptionQrPath = null;

    public function generateRedemptionQr(): void
    {
        try {
            // Validate fields - support both customer_id and email
            $validationRules = [
                'data.redeem_company_id' => 'required',
                'data.redeem_points' => 'required|numeric|min:1',
                'data.redemption_description' => 'required',
            ];

            // Require at least one customer identifier
            if (empty($this->data['redeem_customer_id']) && empty($this->data['redeem_customer_email'])) {
                Notification::make()
                    ->title('Customer Identifier Required')
                    ->body('Please provide either Customer ID or Customer Email')
                    ->danger()
                    ->send();
                return;
            }

            // Add validation for the provided identifier
            if (!empty($this->data['redeem_customer_id'])) {
                $validationRules['data.redeem_customer_id'] = 'required|string';
            }
            if (!empty($this->data['redeem_customer_email'])) {
                $validationRules['data.redeem_customer_email'] = 'required|email';
            }

            $this->validate($validationRules);

            // Additional security check
            if (!$this->validateCompanyAccess($this->data['redeem_company_id'])) {
                return;
            }

            $companyId = $this->data['redeem_company_id'];
            $customerId = $this->data['redeem_customer_id'] ?? null;
            $customerEmail = $this->data['redeem_customer_email'] ?? null;
            $redeemPoints = (int) $this->data['redeem_points'];

            // Use the service to check eligibility and create transaction
            $loyaltyService = app(LoyaltyService::class);
            
            $eligibility = $loyaltyService->checkRedemptionEligibility($customerId, $customerEmail, $companyId, $redeemPoints);
            
            if (!$eligibility['eligible']) {
                $customerIdentifier = $customerId ?? $customerEmail;
                Notification::make()
                    ->title('Insufficient Points')
                    ->body("Customer {$customerIdentifier} only has {$eligibility['current_balance']} points available for this company")
                    ->danger()
                    ->send();
                return;
            }

            // Create redemption transaction using the service
            $redemptionTransaction = $loyaltyService->createRedemptionTransaction([
                'customer_id' => $customerId,
                'customer_email' => $customerEmail,
                'company_id' => $companyId,
                'redeem_points' => $redeemPoints,
                'redemption_description' => $this->data['redemption_description'],
                'created_by' => Auth::id(),
            ]);

            $transactionId = $redemptionTransaction->id;

            Log::info('Redemption record created (pending)', [
                'id' => $redemptionTransaction->id, 
                'transaction_id' => $transactionId,
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'customer_email' => $customerEmail,
                'points_to_redeem' => $redeemPoints
            ]);

            // Generate the webhook URL for redemption confirmation
            //$webhookUrl = url('loyalty/confirm-redemption/' . $transactionId);

            
            // $qrCode = QrCode::format('png')
            //     ->size(300)
            //     ->margin(2)
            //     ->errorCorrection('M')
            //     ->generate($webhookUrl);

            $payload = [
                'transaction_id' => $transactionId,
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'customer_email' => $customerEmail,
                'redeem_points' => $redeemPoints,
                'redemption_description' => $this->data['redemption_description'],
                'created_by' => Auth::id(),
            ];

            $jsonPayload = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            $qrCode = QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->errorCorrection('M')
                ->generate($jsonPayload);

            $qrFileName = 'qr-codes/' . $transactionId . '.png';

            if (!Storage::disk('public')->exists('qr-codes')) {
                Storage::disk('public')->makeDirectory('qr-codes');
            }

            Storage::disk('public')->put($qrFileName, $qrCode);

            $redemptionTransaction->update(['qr_code_path' => $qrFileName]);

            $this->redemptionQrPath = asset('storage/' . $qrFileName);
            
            Log::info('Redemption QR Code generated successfully with JSON Payload', [
                'transaction_id' => $transactionId,
                'file_path' => $qrFileName,
                'qr_payload' => $jsonPayload,
                // 'webhook_url' => $webhookUrl,
                'company_id' => $companyId,
                'customer_balance_before' => $eligibility['current_balance']
            ]);

            $customerIdentifier = $customerId ?? $customerEmail;
            Notification::make()
                ->title('Redemption QR Generated Successfully!')
                ->body("Scan QR to confirm redemption for {$customerIdentifier}. Transaction ID: {$transactionId}. Customer has {$eligibility['current_balance']} points available.")
                ->success()
                ->persistent()
                ->send();
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors specifically
            $errors = $e->validator->errors()->all();
            
            Notification::make()
                ->title('Validation Error')
                ->body('Please check: ' . implode(', ', $errors))
                ->danger()
                ->send();

            Log::error('Redemption validation failed', [
                'errors' => $errors,
                'data' => $this->data ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Redemption QR generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data ?? []
            ]);

            Notification::make()
                ->title('Redemption QR Generation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function resetRedemptionData(): void
    {
        $this->redemptionQrPath = null;
    }
}