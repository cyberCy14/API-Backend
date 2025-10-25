<?php
namespace App\Filament\Pages\Concerns;

use App\Models\LoyaltyProgram;
use App\Services\LoyaltyService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

trait HandlesPointCalculation
{
    public ?string $calculatedPoints = null;
    public ?string $qrCodePath = null;
    public ?array $ruleBreakdown = [];
    public ?string $customerEmailDisplay = null;

//     public function calculatePoints(): void
// {
//     try {
//         // Validate fields - support both customer_id and email
//         $validationRules = [
//             'data.company_id' => 'required',
//             'data.loyalty_program_id' => 'required',
//             'data.purchase_amount' => 'required|numeric|min:0.01',
//         ];

//         // Require at least one customer identifier
//         if (empty($this->data['customer_id']) && empty($this->data['customer_email'])) {
//             Notification::make()
//                 ->title('Customer Identifier Required')
//                 ->body('Please provide either Customer ID or Customer Email')
//                 ->danger()
//                 ->send();
//             return;
//         }

//         // Add validation for the provided identifier - FIXED: removed string requirement
//         if (!empty($this->data['customer_id'])) {
//             $validationRules['data.customer_id'] = 'required'; // Accept any type
//         }
//         if (!empty($this->data['customer_email'])) {
//             $validationRules['data.customer_email'] = 'required|email';
//         }

//         $this->validate($validationRules);

//         // Additional security check
//         if (!$this->validateCompanyAccess($this->data['company_id'])) {
//             return;
//         }

//         // Check if customer exists
//         if (!$this->validateCustomerExists($this->data['customer_id'] ?? null, $this->data['customer_email'] ?? null)) {
//             return;
//         }

//         Log::info('Calculating points', $this->data);

//         $loyaltyProgram = LoyaltyProgram::where('id', $this->data['loyalty_program_id'])
//             ->where('company_id', $this->data['company_id'])
//             ->first();
        
//         if (!$loyaltyProgram) {
//             Notification::make()
//                 ->title('Error')
//                 ->body('Loyalty program not found or access denied')
//                 ->danger()
//                 ->send();
//             return;
//         }

//         $purchaseAmount = (float) $this->data['purchase_amount'];
        
//         // Use the service to calculate points
//         $loyaltyService = app(LoyaltyService::class);
//         $result = $loyaltyService->calculatePoints($loyaltyProgram, $purchaseAmount);

//         $this->calculatedPoints = number_format($result['total_points']) . ' points';
//         $this->ruleBreakdown = $result['breakdown'];

//         Log::info('Points calculated', [
//             'total' => $result['total_points'], 
//             'breakdown' => $result['breakdown']
//         ]);

//         $customerIdentifier = $this->data['customer_email'];

//         if (empty($customerIdentifier) && !empty($this->data['customer_id'])) {
//             $user = User::find($this->data['customer_id']);
//             $customerIdentifier = $user?->email ?? 'Customer ID ' . $this->data['customer_id'];
//         }

//         Notification::make()
//             ->title('Points Calculated Successfully!')
//             ->body("Customer {$customerIdentifier} will earn {$result['total_points']} points from this purchase")
//             ->success()
//             ->send();

//     } catch (\Exception $e) {
//         Log::error('Point calculation failed', [
//             'error' => $e->getMessage(),
//             'trace' => $e->getTraceAsString()
//         ]);

//         Notification::make()
//             ->title('Calculation Failed')
//             ->body('Error: ' . $e->getMessage())
//             ->danger()
//             ->send();
//     }
// }


public function calculatePoints(): void
{
    try {
        // A) Validate just the basics
        $rules = [
            'data.company_id'         => 'required',
            'data.loyalty_program_id' => 'required',
            'data.purchase_amount'    => 'required|numeric|min:0.01',
        ];
        if (empty($this->data['customer_id']) && empty($this->data['customer_email'])) {
            Notification::make()->title('Customer Identifier Required')
                ->body('Please provide either Customer ID or Customer Email')
                ->danger()->send();
            return;
        }
        if (!empty($this->data['customer_id']))    $rules['data.customer_id']   = 'required';
        if (!empty($this->data['customer_email'])) $rules['data.customer_email']= 'required|email';
        $this->validate($rules);

        // B) Resolve the customer (no membership checks)
        $customer = $this->resolveCustomer($this->data['customer_id'] ?? null, $this->data['customer_email'] ?? null);
        if (!$customer) {
            Notification::make()->title('Customer Not Found')
                ->body('No matching customer was found.')->danger()->send();
            return;
        }

        // C) Program must belong to the selected company (the ONLY access rule)
        $program = LoyaltyProgram::where('id', $this->data['loyalty_program_id'])
            ->where('company_id', $this->data['company_id'])
            ->first();
        if (!$program) {
            Notification::make()->title('Error')
                ->body('Loyalty program not found for the selected company.')
                ->danger()->send();
            return;
        }

        // D) Compute points by rules (or replace with a fixed formula if you prefer)
        $service = app(LoyaltyService::class);
        $amount  = (float) $this->data['purchase_amount'];
        $calc    = $service->calculatePoints($program, $amount);

        $points = (int) ($calc['total_points'] ?? 0);
        if ($points <= 0) {
            Notification::make()->title('No Points Earned')
                ->body('Purchase did not meet the minimum rule to earn points.')
                ->warning()->send();
            return;
        }

        // E) Persist the EARN as a completed transaction.
        //    If there are no previous transactions in this company, the service treats
        //    the "before" balance as 0. So it becomes 0 + earned points.
        $tx = $service->createEarningTransaction([
            'customer_id'         => $this->data['customer_id'] ?? null,
            'customer_email'      => $this->data['customer_email'] ?? null,
            'company_id'          => (int) $this->data['company_id'],
            'loyalty_program_id'  => (int) $this->data['loyalty_program_id'],
            'purchase_amount'     => $amount,
            'points_earned'       => $points,
            'rule_breakdown'      => $calc['breakdown'] ?? [],
        ]);

        $this->calculatedPoints = number_format($points) . ' points';
        $this->ruleBreakdown    = $calc['breakdown'] ?? [];

        $who = $customer->email ?? ('Customer ID ' . $customer->id);
        Notification::make()->title('Points Added!')
            ->body("{$who} earned {$points} points. Current balance: {$tx->balance}.")
            ->success()->send();

        Log::info('Points added', ['transaction_id' => $tx->transaction_id]);

    } catch (\Throwable $e) {
        Log::error('Point calculation failed', ['error' => $e->getMessage()]);
        Notification::make()->title('Calculation Failed')
            ->body('Error: ' . $e->getMessage())->danger()->send();
    }
}

private function resolveCustomer(?string $customerId, ?string $customerEmail): ?User
{
    if (!empty($customerId))    return User::find($customerId);
    if (!empty($customerEmail)) return User::where('email', $customerEmail)->first();
    return null;
}



    public function resetCalculation(): void
    {
        $this->calculatedPoints = null;
        $this->qrCodePath = null;  // Reset this too
        $this->ruleBreakdown = [];
    }

    public function fetchCustomerEmail(): void
    {
        if (!empty($this->data['customer_id'])) {
            $user = User::find($this->data['customer_id']);
            $this->customerEmailDisplay = $user ? $user->email : null;
        } else {
            $this->customerEmailDisplay = null;
        }
    }

    private function validateCustomerExists(?string $customerId, ?string $customerEmail): bool
    {
        if (!empty($customerId) && !User::where('id', $customerId)->exists()) {
            Notification::make()
                ->title('Customer Not Found')
                ->body('The provided Customer ID does not exist in the system.')
                ->danger()
                ->send();
            return false;
        }

        if (!empty($customerEmail) && !User::where('email', $customerEmail)->exists()) {
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