<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Traits\HasRoleHelpers;
use App\Filament\Pages\Concerns\HandlesPointCalculation;
use App\Filament\Pages\Concerns\HandlesCustomerLookup;
use App\Filament\Pages\Concerns\HandlesPointRedemption;
use App\Filament\Pages\Concerns\HandlesCompanyAccess;
use App\Filament\Pages\Schema\PointCalculatorSchema;

class PointCalculator extends Page implements HasForms
{
    use InteractsWithForms;
    use HasRoleHelpers;
    use HandlesPointCalculation;
    use HandlesCustomerLookup;
    use HandlesPointRedemption;
    use HandlesCompanyAccess;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static string $view = 'filament.pages.point-calculator';
    protected static ?string $navigationGroup = 'Loyalty Program Tools';
    protected static ?string $title = 'Point Calculator & Customer Manager';

    public ?array $data = [];

    public function mount(): void
    {
        $this->initializeForm();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema(PointCalculatorSchema::getSchema($this))
            ->statePath('data');
    }

    /**
     * Check if the current user can view this page
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
    
        if (!$user) {
            return false;
        }
    
        // Filament Shield always grants full access to super_admin
        if ($user->hasRole('super_admin')) {
            return true;
        }
    
        // Add any other roles you want to allow here
        return $user->hasRole('handler');
    }
    
    public function resetCalculation(): void
    {
        $this->calculatedPoints = null;
        $this->ruleBreakdown = [];
    }

    
}