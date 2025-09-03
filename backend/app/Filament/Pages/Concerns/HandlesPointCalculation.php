<?php
namespace App\Filament\Pages\Concerns;

use App\Models\LoyaltyProgram;
use App\Services\LoyaltyService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait HandlesPointCalculation
{
    // Earning calculation properties
    public ?string $calculatedPoints = null;
    public ?string $qrCodePath = null;
    public ?array $ruleBreakdown = [];

    public function calculatePoints(): void
    {
        try {
            // Validate fields - support both customer_id and email
            $validationRules = [
                'data.company_id' => 'required',
                'data.loyalty_program_id' => 'required',
                'data.purchase_amount' => 'required|numeric|min:0.01',
            ];

            // Require at least one customer identifier
            if (empty($this->data['customer_id']) && empty($this->data['customer_email'])) {
                Notification::make()
                    ->title('Customer Identifier Required')
                    ->body('Please provide either Customer ID or Customer Email')
                    ->danger()
                    ->send();
                return;
            }

            // Add validation for the provided identifier
            if (!empty($this->data['customer_id'])) {
                $validationRules['data.customer_id'] = 'required|string';
            }
            if (!empty($this->data['customer_email'])) {
                $validationRules['data.customer_email'] = 'required|email';
            }

            $this->validate($validationRules);

            // Additional security check
            if (!$this->validateCompanyAccess($this->data['company_id'])) {
                return;
            }

            Log::info('Calculating points', $this->data);

            $loyaltyProgram = LoyaltyProgram::where('id', $this->data['loyalty_program_id'])
                ->where('company_id', $this->data['company_id'])
                ->first();
            
            if (!$loyaltyProgram) {
                Notification::make()
                    ->title('Error')
                    ->body('Loyalty program not found or access denied')
                    ->danger()
                    ->send();
                return;
            }

            $purchaseAmount = (float) $this->data['purchase_amount'];
            
            // Use the service to calculate points
            $loyaltyService = app(LoyaltyService::class);
            $result = $loyaltyService->calculatePoints($loyaltyProgram, $purchaseAmount);

            $this->calculatedPoints = number_format($result['total_points']) . ' points';
            $this->ruleBreakdown = $result['breakdown'];

            Log::info('Points calculated', [
                'total' => $result['total_points'], 
                'breakdown' => $result['breakdown']
            ]);

            $customerIdentifier = $this->data['customer_id'] ?? $this->data['customer_email'];
            Notification::make()
                ->title('Points Calculated Successfully!')
                ->body("Customer {$customerIdentifier} will earn {$result['total_points']} points from this purchase")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Point calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Calculation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function generateQrAndCreditPoints(): void
    {
        try {
            // Validate fields - support both customer_id and email
            $validationRules = [
                'data.company_id' => 'required',
                'data.loyalty_program_id' => 'required',
                'data.purchase_amount' => 'required|numeric|min:0.01',
            ];

            // Require at least one customer identifier
            if (empty($this->data['customer_id']) && empty($this->data['customer_email'])) {
                Notification::make()
                    ->title('Customer Identifier Required')
                    ->body('Please provide either Customer ID or Customer Email')
                    ->danger()
                    ->send();
                return;
            }

            // Add validation for the provided identifier
            if (!empty($this->data['customer_id'])) {
                $validationRules['data.customer_id'] = 'required|string';
            }
            if (!empty($this->data['customer_email'])) {
                $validationRules['data.customer_email'] = 'required|email';
            }

            $this->validate($validationRules);

            // Additional security check
            if (!$this->validateCompanyAccess($this->data['company_id'])) {
                return;
            }

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
                'customer_id' => $this->data['customer_id'] ?? null,
                'customer_email' => $this->data['customer_email'] ?? null,
                'company_id' => $this->data['company_id'],
                'loyalty_program_id' => $this->data['loyalty_program_id'],
                'points_earned' => $totalPoints,
                'purchase_amount' => $this->data['purchase_amount'],
                'rule_breakdown' => $this->ruleBreakdown,
                'created_by' => Auth::id(),
            ];

            $customerPoint = $loyaltyService->createEarningTransaction($transactionData);

            Log::info('Customer point record created (pending)', [
                'id' => $customerPoint->id, 
                'transaction_id' => $customerPoint->transaction_id
            ]);

            // Generate the webhook URL and QR code
            $webhookUrl = url('/loyalty/confirm-earning/' . $customerPoint->transaction_id);
            $this->qrCodePath = $loyaltyService->generateTransactionQr($customerPoint, $webhookUrl);

            Log::info('Earning QR Code generated successfully', [
                'transaction_id' => $customerPoint->transaction_id,
                'webhook_url' => $webhookUrl
            ]);

            $customerIdentifier = $this->data['customer_id'] ?? $this->data['customer_email'];
            Notification::make()
                ->title('QR Code Generated Successfully!')
                ->body("Scan QR to credit points to {$customerIdentifier}. Transaction ID: {$customerPoint->transaction_id}")
                ->success()
                ->persistent()
                ->send();

        } catch (\Exception $e) {
            Log::error('QR Code generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data ?? []
            ]);

            Notification::make()
                ->title('QR Code Generation Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        }
    }

    private function resetCalculation(): void
    {
        $this->calculatedPoints = null;
        $this->qrCodePath = null;
        $this->ruleBreakdown = [];
    }
}