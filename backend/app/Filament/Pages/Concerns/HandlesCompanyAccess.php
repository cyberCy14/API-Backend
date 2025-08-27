<?php

namespace App\Filament\Pages\Concerns;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

trait HandlesCompanyAccess
{
    /**
     * Initialize form with default company values
     */
    public function initializeForm(): void
    {
        $user = Auth::user();
        $availableCompanies = $this->getAvailableCompanies();
        
        // Pre-fill with appropriate company based on user role
        $defaultCompanyId = null;
        
        if ($this->isSuperAdmin($user)) {
            // For superadmin, use first available company
            $defaultCompanyId = $availableCompanies->first()?->id;
        } elseif ($this->isHandler($user)) {
            // For handler, use their first company
            $defaultCompanyId = $user->companies->first()?->id;
        }

        $this->form->fill([
            'company_id' => $defaultCompanyId,
            'search_company_id' => $defaultCompanyId,
            'redeem_company_id' => $defaultCompanyId,
        ]);
    }


    /**
     * Get company options for select fields based on user permissions
     */

    /**
     * Check if user can access a specific company
     */

    /**
     * Validate company access and show notification if denied
     */
    public function validateCompanyAccess($companyId): bool
    {
        if ($companyId && !$this->canAccessCompany($companyId)) {
            Notification::make()
                ->title('Access Denied')
                ->body('You do not have access to this company')
                ->danger()
                ->send();
            return false;
        }
        return true;
    }
}