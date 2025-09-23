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
    public ?string $customerEmailDisplay = null;

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

            // Check if customer exists
            if (!$this->validateCustomerExists($this->data['customer_id'] ?? null, $this->data['customer_email'] ?? null)) {
                return;
            }

            Log::info('Calculating points', $this->data);

           $loyaltyProgram = LoyaltyProgram::find($this->data['loyalty_program_id']);

        if (!$loyaltyProgram) {
            $loyaltyProgram = LoyaltyProgram::where('company_id', $this->data['company_id'])
                ->where('is_active', true)
                ->first();

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

            // Check if customer exists
            if (!$this->validateCustomerExists($this->data['customer_id'] ?? null, $this->data['customer_email'] ?? null)) {
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

            // Fetch customer email if customer_id is provided but email is not
            $customerEmail = $this->data['customer_email'] ?? null;
            if (empty($customerEmail) && !empty($this->data['customer_id'])) {
                $user = \App\Models\User::find($this->data['customer_id']);
                $customerEmail = $user ? $user->email : null;
            }

            // Use the service to create the transaction
            $loyaltyService = app(LoyaltyService::class);

            $transactionData = [
                'customer_id' => $this->data['customer_id'] ?? null,
                'customer_email' => $customerEmail,
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

// Prepare QR payload (instead of webhook URL)
$qrPayload = [
    'transaction_id'   => $customerPoint->transaction_id,
    'action'           => 'earn',
    'customer'         => $this->data['customer_id'] ?? $this->data['customer_email'],
    'company'          => $customerPoint->company->company_name ?? null,
    'points'           => $totalPoints,
    'status'           => 'pending',
    'balance'          => null, // will be filled after confirmation
    'date'             => now()->toDateTimeString(),
];

// Generate QR code directly from payload JSON
$this->qrCodePath = $loyaltyService->generateTransactionQr($customerPoint, json_encode($qrPayload));

Log::info('Earning QR Code generated successfully', [
    'transaction_id' => $customerPoint->transaction_id,
    'qr_payload'     => $qrPayload,
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

    public function fetchCustomerEmail(): void
    {
        if (!empty($this->data['customer_id'])) {
            $user = \App\Models\User::find($this->data['customer_id']);
            $this->customerEmailDisplay = $user ? $user->email : null;
        } else {
            $this->customerEmailDisplay = null;
        }
    }

    private function validateCustomerExists(?string $customerId, ?string $customerEmail): bool
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