<?php
namespace App\Filament\Pages\Concerns;

use App\Traits\HasRoleHelpers;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

trait HandlesCompanyAccess
{
    use HasRoleHelpers;

    /**
     * Initialize form with default company values
     */
    public function initializeForm(): void
    {
        $defaultCompanyId = $this->getDefaultCompanyId();

        $this->form->fill([
            'company_id' => $defaultCompanyId,
            'search_company_id' => $defaultCompanyId,
            'redeem_company_id' => $defaultCompanyId,
        ]);
    }

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