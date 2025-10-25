<?php
namespace App\Filament\Pages\Concerns;

use App\Models\CustomerPoint;
use App\Models\LoyaltyReward;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\Services\LoyaltyService;
use Filament\Forms\Validation\ValidationException;
use Exception;
use Illuminate\Support\Str;

trait HandlesPointRedemption
{
    // Redemption properties
    public ?string $redemptionQrPath = null;
    public ?string $redemptionCustomerEmailDisplay = null;
    public ?int $redemptionCustomerBalance = null;

    public function generateRedemptionQr(): void
    {
        try {
            $validationRules = [
                'data.redeem_company_id' => 'required',
                'data.redeem_points' => 'required|numeric|min:1',
                'data.redemption_description' => 'required',
            ];

            if (!empty($this->data['loyalty_program_id_redeem'])) {
                $validationRules['data.loyalty_program_id_redeem'] = 'required';
            }

            if (!empty($this->data['reward_id'])) {
                $validationRules['data.reward_id'] = 'exists:loyalty_rewards,id';
            }

            if (empty($this->data['redeem_customer_id']) && empty($this->data['redeem_customer_email'])) {
                Notification::make()
                    ->title('Customer Identifier Required')
                    ->body('Please provide either Customer ID or Customer Email')
                    ->danger()
                    ->send();
                return;
            }

            if (!empty($this->data['redeem_customer_id'])) {
                $validationRules['data.redeem_customer_id'] = 'required|string';
            }
            if (!empty($this->data['redeem_customer_email'])) {
                $validationRules['data.redeem_customer_email'] = 'required|email';
            }

            $this->validate($validationRules);

            if (!$this->validateCompanyAccess($this->data['redeem_company_id'])) {
                return;
            }

            if (!$this->validateRedemptionCustomerExists(
                $this->data['redeem_customer_id'] ?? null,
                $this->data['redeem_customer_email'] ?? null
            )) {
                return;
            }

            $companyId = $this->data['redeem_company_id'];
            $customerId = $this->data['redeem_customer_id'] ?? null;
            $customerEmail = $this->data['redeem_customer_email'] ?? null;
            $redeemPoints = (int) $this->data['redeem_points'];
            $rewardId = $this->data['reward_id'] ?? null;

            $reward = null;
            if ($rewardId) {
                $reward = LoyaltyReward::find($rewardId);
                if (!$reward || !$reward->is_active) {
                    Notification::make()
                        ->title('Invalid Reward')
                        ->body('The selected reward is not available or inactive')
                        ->danger()
                        ->send();
                    return;
                }

                if ($reward->point_cost != $redeemPoints) {
                    Notification::make()
                        ->title('Points Mismatch')
                        ->body('The points to redeem do not match the reward\'s point cost')
                        ->danger()
                        ->send();
                    return;
                }
            }

            $loyaltyService = app(LoyaltyService::class);
            $eligibility = $loyaltyService->checkRedemptionEligibility(
                $customerId,
                $customerEmail,
                $companyId,
                $redeemPoints
            );

            if (!$eligibility['eligible']) {
                $customerIdentifier = $customerId ?? $customerEmail;
                Notification::make()
                    ->title('Insufficient Points')
                    ->body("Customer {$customerIdentifier} only has {$eligibility['current_balance']} points available for this company")
                    ->danger()
                    ->send();
                return;
            }

            if (empty($customerEmail) && !empty($customerId)) {
                $user = \App\Models\User::find($customerId);
                $customerEmail = $user?->email;
            }

          

            $transactionId = (string) Str::uuid();

                $redemptionData = [
                    'transaction_id' => $transactionId,
                    'customer_id' => $customerId,
                    'customer_email' => $customerEmail,
                    'company_id' => $companyId,
                    'redeem_points' => $redeemPoints,
                    'redemption_description' => $this->data['redemption_description'],
                    'created_by' => Auth::id(),
                ];

            if ($reward) {
                $redemptionData['reward_id'] = $reward->id;
                $redemptionData['reward_name'] = $reward->reward_name;
                $redemptionData['reward_type'] = $reward->reward_type;
            }

            $redemptionTransaction = $loyaltyService->createRedemptionTransaction($redemptionData);
            $transactionId = $redemptionTransaction->transaction_id;

            Log::info('Redemption record created (pending)', [
                'id' => $redemptionTransaction->id,
                'transaction_id' => $transactionId,
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'customer_email' => $customerEmail,
                'points_to_redeem' => $redeemPoints,
                'reward_id' => $rewardId,
                'reward_name' => $reward?->reward_name,
            ]);

            $payload = [
                'transaction_id' => $transactionId,
                'action' => 'redeem',
                'customer' => $customerId ?? $customerEmail,
                'company' => $redemptionTransaction->company->company_name ?? null,
                'points' => $redeemPoints,
                'description' => $this->data['redemption_description'],
                'status' => 'pending',
                'date' => now()->toDateTimeString(),
            ];

            if ($reward) {
                $payload['reward'] = [
                    'id' => $reward->id,
                    'name' => $reward->reward_name,
                    'type' => $reward->reward_type,
                    'point_cost' => $reward->point_cost,
                ];

                if ($reward->discount_percentage) {
                    $payload['reward']['discount_percentage'] = $reward->discount_percentage;
                }
                if ($reward->discount_value) {
                    $payload['reward']['discount_value'] = $reward->discount_value;
                }
                if ($reward->voucher_code) {
                    $payload['reward']['voucher_code'] = $reward->voucher_code;
                }
                if ($reward->expiration_days) {
                    $payload['reward']['expiration_days'] = $reward->expiration_days;
                    $payload['reward']['expires_at'] = now()->addDays($reward->expiration_days)->toDateTimeString();
                }
            }

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
                'company_id' => $companyId,
                'customer_balance_before' => $eligibility['current_balance'],
                'reward_name' => $reward?->reward_name,
            ]);

            $customerIdentifier = $customerId ?? $customerEmail;
            $rewardText = $reward ? " for {$reward->reward_name}" : '';

            Notification::make()
                ->title('Redemption QR Generated Successfully!')
                ->body("Scan QR to confirm redemption{$rewardText} for {$customerIdentifier}. Transaction ID: {$transactionId}. Customer has {$eligibility['current_balance']} points available.")
                ->success()
                ->persistent()
                ->send();

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->validator->errors()->all();

            Notification::make()
                ->title('Validation Error')
                ->body('Please check: ' . implode(', ', $errors))
                ->danger()
                ->send();

            Log::error('Redemption validation failed', [
                'errors' => $errors,
                'data' => $this->data ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error('Redemption QR generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data ?? [],
            ]);

            Notification::make()
                ->title('Redemption QR Generation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    public function checkRedemptionCustomerBalance(): void
    {
        try {
            if (empty($this->data['redeem_company_id'])) {
                Notification::make()
                    ->title('Company Required')
                    ->body('Please select a company first')
                    ->warning()
                    ->send();
                return;
            }

            $customerId = $this->data['redeem_customer_id'] ?? null;
            $customerEmail = $this->data['redeem_customer_email'] ?? null;

            if (empty($customerId) && empty($customerEmail)) {
                Notification::make()
                    ->title('Customer Required')
                    ->body('Please provide either Customer ID or Customer Email')
                    ->warning()
                    ->send();
                return;
            }

            if (!$this->validateRedemptionCustomerExists($customerId, $customerEmail)) {
                $this->redemptionCustomerBalance = null;
                return;
            }

            $loyaltyService = app(LoyaltyService::class);
           $balance = $loyaltyService->getAvailableBalance(
    $customerId,
    $customerEmail,
    $this->data['redeem_company_id']
);

            $this->redemptionCustomerBalance = $balance;
            $customerIdentifier = $customerId ?? $customerEmail;

            Notification::make()
                ->title('Balance Retrieved')
                ->body("Customer {$customerIdentifier} has " . number_format($balance) . " points available")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Failed to check customer balance', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId ?? null,
                'customer_email' => $customerEmail ?? null,
                'company_id' => $this->data['redeem_company_id'] ?? null,
            ]);

            Notification::make()
                ->title('Balance Check Failed')
                ->body('Error retrieving customer balance: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->redemptionCustomerBalance = null;
        }
    }

    
 
    
    
    public function resetRedemptionData(): void
    {
        $this->redemptionQrPath = null;
        $this->redemptionCustomerBalance = null;
    }

    public function fetchRedemptionCustomerEmail(): void
    {
        if (!empty($this->data['redeem_customer_id'])) {
            $user = \App\Models\User::find($this->data['redeem_customer_id']);
            $this->redemptionCustomerEmailDisplay = $user?->email;

            if ($user && !empty($this->data['redeem_company_id'])) {
                $this->checkRedemptionCustomerBalance();
            }
        } else {
            $this->redemptionCustomerEmailDisplay = null;
            $this->redemptionCustomerBalance = null;
        }
    }

    private function validateRedemptionCustomerExists(?string $customerId, ?string $customerEmail): bool
    {
        if (!empty($customerId) && !\App\Models\User::where('id', $customerId)->exists()) {
            Notification::make()
                ->title('Customer Not Found')
                ->body('The provided Customer ID does not exist in the system.')
                ->danger()
                ->send();
            return false;
        }

        if (!empty($customerEmail) && !\App\Models\User::where('email', $customerEmail)->exists()) {
            Notification::make()
                ->title('Customer Not Found')
                ->body('The provided Customer Email does not exist in the system.')
                ->danger()
                ->send();
            return false;
        }

        return true;
    }
}
