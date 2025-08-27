<?php

namespace App\Filament\Pages\Concerns;

use App\Models\CustomerPoint;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

trait HandlesCustomerLookup
{
    // Customer search properties
    public ?int $customerBalance = null;
    public ?array $customerTransactions = [];

    public function searchCustomer(): void
    {
        try {
            // Validate only the fields relevant to the 'customer_lookup' tab
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

            $this->customerBalance = CustomerPoint::getCustomerBalance($customerEmail, $companyId);
            $this->customerTransactions = CustomerPoint::getCustomerTransactionHistory($customerEmail, $companyId)->toArray();

            Notification::make()
                ->title('Customer Found')
                ->body("Customer has {$this->customerBalance} points available")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Customer search failed', [
                'error' => $e->getMessage(),
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