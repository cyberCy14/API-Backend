<?php

namespace App\Filament\Pages\Concerns;

use App\Models\CustomerPoint;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use App\Services\LoyaltyService;

trait HandlesCustomerLookup
{
    // Customer lookup properties
    public ?int $customerBalance = null;
    public ?array $customerTransactions = [];

    public function searchCustomer(): void
    {
        try {
            // Validate the search fields
            $this->validate([
                'data.search_company_id' => 'required',
                'data.search_customer_email' => 'required|email',
            ]);

            // Additional security check
            if (!$this->validateCompanyAccess($this->data['search_company_id'])) {
                return;
            }

            $companyId = $this->data['search_company_id'];
            $customerEmail = $this->data['search_customer_email'];

            Log::info('Searching customer', [
                'company_id' => $companyId,
                'customer_email' => $customerEmail
            ]);

            // Use the service to get customer summary for THIS SPECIFIC COMPANY
            $loyaltyService = app(LoyaltyService::class);
            $customerSummary = $loyaltyService->getCustomerSummary($customerEmail, $companyId);

            $this->customerBalance = $customerSummary['balance'];
            $this->customerTransactions = $customerSummary['transactions'];

            Log::info('Customer search completed', [
                'company_id' => $companyId,
                'customer_email' => $customerEmail,
                'balance' => $this->customerBalance,
                'transaction_count' => count($this->customerTransactions)
            ]);

            if ($this->customerBalance === 0 && empty($this->customerTransactions)) {
                Notification::make()
                    ->title('Customer Not Found')
                    ->body("No transactions found for {$customerEmail} with this company")
                    ->warning()
                    ->send();
            } else {
                Notification::make()
                    ->title('Customer Found!')
                    ->body("Found {$this->customerBalance} points and " . count($this->customerTransactions) . " transactions for this company")
                    ->success()
                    ->send();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors specifically
            $errors = $e->validator->errors()->all();
            
            Notification::make()
                ->title('Validation Error')
                ->body('Please check: ' . implode(', ', $errors))
                ->danger()
                ->send();

            Log::error('Customer search validation failed', [
                'errors' => $errors,
                'data' => $this->data ?? []
            ]);

        } catch (\Exception $e) {
            Log::error('Customer search failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data ?? []
            ]);

            Notification::make()
                ->title('Search Failed')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetCustomerData(): void
    {
        $this->customerBalance = null;
        $this->customerTransactions = [];
    }
}