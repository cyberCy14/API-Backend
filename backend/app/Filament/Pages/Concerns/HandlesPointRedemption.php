<?php

namespace App\Filament\Pages\Concerns;

use App\Models\CustomerPoint;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

trait HandlesPointRedemption
{
    // Redemption properties
    public ?string $redemptionQrPath = null;

    public function generateRedemptionQr(): void
    {
        try {
            // Validate only the fields relevant to the 'redeem_points' tab
            $this->validate([
                'data.redeem_company_id' => 'required',
                'data.redeem_customer_email' => 'required|email',
                'data.redeem_points' => 'required|numeric|min:1',
                'data.redemption_description' => 'required',
            ]);

            // Additional security check
            if (!$this->validateCompanyAccess($this->data['redeem_company_id'])) {
                return;
            }

            $companyId = $this->data['redeem_company_id'];
            $customerEmail = $this->data['redeem_customer_email'];
            $redeemPoints = (int) $this->data['redeem_points'];

            $currentBalance = CustomerPoint::getCustomerBalance($customerEmail, $companyId);

            if ($currentBalance < $redeemPoints) {
                Notification::make()
                    ->title('Insufficient Points')
                    ->body("Customer only has {$currentBalance} points available")
                    ->danger()
                    ->send();
                return;
            }

            $transactionId = 'RED-' . strtoupper(Str::random(10));

            // Note: Set loyalty_program_id to null since redemptions might not be tied to specific programs
            $redemptionTransaction = CustomerPoint::create([
                'customer_email' => $customerEmail,
                'company_id' => $companyId,
                'loyalty_program_id' => null, // Fixed: explicitly set to null
                'points_earned' => -$redeemPoints,
                'purchase_amount' => null,
                'transaction_id' => $transactionId,
                'transaction_type' => 'redemption',
                'status' => 'pending',
                'redemption_description' => $this->data['redemption_description'],
                'created_by' => Auth::id(),
            ]);

            Log::info('Redemption record created (pending)', [
                'id' => $redemptionTransaction->id, 
                'transaction_id' => $transactionId
            ]);

            // Generate the webhook URL for redemption confirmation
            $webhookUrl = url('loyalty/confirm-redemption/' . $transactionId);
            

            $qrCode = QrCode::format('png')
                ->size(300)
                ->margin(2)
                ->errorCorrection('M')
                ->generate($webhookUrl);

            $qrFileName = 'qr-codes/' . $transactionId . '.png';

            if (!Storage::disk('public')->exists('qr-codes')) {
                Storage::disk('public')->makeDirectory('qr-codes');
            }

            Storage::disk('public')->put($qrFileName, $qrCode);

            $redemptionTransaction->update(['qr_code_path' => $qrFileName]);

            $this->redemptionQrPath = asset('storage/' . $qrFileName);
            
            Log::info('Redemption QR Code generated successfully with webhook URL', [
                'transaction_id' => $transactionId,
                'file_path' => $qrFileName,
                'webhook_url' => $webhookUrl
            ]);

            Notification::make()
                ->title('Redemption QR Generated Successfully!')
                ->body("Scan QR to confirm redemption. Transaction ID: {$transactionId}")
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