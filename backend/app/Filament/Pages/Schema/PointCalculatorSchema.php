<?php

namespace App\Filament\Pages\Schema;

use App\Traits\HasRoleHelpers;
use App\Filament\Pages\Concerns\HandlesCompanyAccess;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use App\Models\LoyaltyProgram;
use Illuminate\Support\Facades\Auth;

class PointCalculatorSchema
{
    use HasRoleHelpers, HandlesCompanyAccess;

    public static function getSchema($livewire): array
    {
        $user = Auth::user();
        $companyOptions = (new static())->getCompanyOptions($user);

        $isHandler = $livewire->isHandler($user);

        return [
            Tabs::make('calculator_tabs')
                ->activeTab(1)
                ->tabs([
                    self::getEarnPointsTab($livewire, $companyOptions, $isHandler),
                    self::getCustomerLookupTab($livewire, $companyOptions, $isHandler),
                    self::getRedeemPointsTab($livewire, $companyOptions, $isHandler),
                ])
                ->live(),
        ];
    }

    private static function getEarnPointsTab($livewire, $companyOptions, $isHandler): Tabs\Tab
    {
        return Tabs\Tab::make('earn_points')
            ->label('Earn Points')
            ->icon('heroicon-o-plus-circle')
            ->schema([
                Section::make('Select Company & Program')
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->options($companyOptions)
                            ->required()
                            ->live()
                            ->disabled($isHandler && count($companyOptions) === 1)
                            ->helperText($isHandler ? 'You can only manage loyalty programs for your assigned companies' : null)
                            ->afterStateUpdated(function ($state, callable $set) use ($livewire) {
                                if (!$livewire->validateCompanyAccess($state)) {
                                    $set('company_id', null);
                                    return;
                                }
                                
                                $set('loyalty_program_id', null);
                                $livewire->resetCalculation();
                            }),

                        Select::make('loyalty_program_id')
                            ->label('Loyalty Program')
                            ->options(function (Get $get) use ($livewire) {
                                $companyId = $get('company_id');
                                if (!$companyId || !$livewire->canAccessCompany($companyId)) {
                                    return [];
                                }

                                return LoyaltyProgram::where('company_id', $companyId)
                                    ->whereHas('rules', fn($query) => $query->where('is_active', true))
                                    ->where('is_active', true)
                                    ->pluck('program_name', 'id');
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn () => $livewire->resetCalculation())
                            ->disabled(fn (Get $get): bool => !$get('company_id'))
                            ->helperText('Only programs with active rules are shown'),
                    ])
                    ->columns(2),

                Section::make('Purchase Details')
                    ->schema([
                        TextInput::make('purchase_amount')
                            ->label('Purchase Amount')
                            ->prefix('PHP')
                            ->numeric()
                            ->minValue(0.01)
                            ->step(0.01)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $livewire->resetCalculation()),

                        TextInput::make('customer_email')
                            ->label('Customer Email')
                            ->email()
                            ->required()
                            ->helperText('Points will be credited to this customer')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn () => $livewire->resetCalculation()),
                    ])
                    ->columns(2),

                Section::make('Calculation Results')
                    ->schema([
                        Placeholder::make('calculated_points')
                            ->label('Total Points Earned')
                            ->content(fn ($get, $livewire): string =>
                                $livewire->calculatedPoints ?? 'Click "Calculate Points" to see results'
                            )
                            ->extraAttributes([
                                'class' => 'text-2xl font-bold text-green-600'
                            ]),

                        Placeholder::make('rule_breakdown')
                            ->label('Points Breakdown')
                            ->content(fn ($get, $livewire): string => 
                                empty($livewire->ruleBreakdown)
                                    ? 'No calculation performed yet'
                                    : collect($livewire->ruleBreakdown)
                                        ->map(fn ($rule) => "• {$rule['rule_name']}: {$rule['points']} points")
                                        ->implode("\n")
                            ),
                    ])
                    ->columns(2)
                    ->visible(fn ($get, $livewire): bool => !empty($livewire->calculatedPoints)),

                Actions::make([
                    Action::make('calculate')
                        ->label('Calculate Points')
                        ->icon('heroicon-o-calculator')
                        ->color('primary')
                        ->action('calculatePoints')
                        ->disabled(fn (Get $get): bool =>
                            empty($get('company_id')) ||
                            empty($get('loyalty_program_id')) ||
                            empty($get('purchase_amount')) ||
                            empty($get('customer_email'))
                        ),

                    Action::make('generate_qr')
                        ->label('Generate QR Code & Credit Points')
                        ->icon('heroicon-o-qr-code')
                        ->color('success')
                        ->action('generateQrAndCreditPoints')
                        ->visible(fn ($get, $livewire): bool => !empty($livewire->calculatedPoints))
                        ->requiresConfirmation()
                        ->modalHeading('Credit Points to Customer')
                        ->modalDescription('This will generate a QR code. Scanning it will credit the points to the customer account.'),
                ])->alignEnd(),
            ]);
    }

    private static function getCustomerLookupTab($livewire, $companyOptions, $isHandler): Tabs\Tab
    {
        return Tabs\Tab::make('customer_lookup')
            ->label('Customer Lookup')
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                Section::make('Search Customer')
                    ->schema([
                        Select::make('search_company_id')
                            ->label('Company')
                            ->options($companyOptions)
                            ->required()
                            ->disabled($isHandler && count($companyOptions) === 1)
                            ->helperText($isHandler ? 'You can only search customers from your assigned companies' : null)
                            ->live()
                            ->afterStateUpdated(function ($state) use ($livewire) {
                                if (!$livewire->validateCompanyAccess($state)) {
                                    return;
                                }
                                $livewire->resetCustomerData();
                            }),

                        TextInput::make('search_customer_email')
                            ->label('Customer Email')
                            ->email()
                            ->required()
                            ->helperText('Enter customer email to view their points balance'),
                    ])
                    ->columns(2),

                Section::make('Customer Information')
                    ->schema([
                        Placeholder::make('customer_balance')
                            ->label('Current Points Balance')
                            ->content(fn ($get, $livewire): string =>
                                $livewire->customerBalance !== null
                                    ? number_format($livewire->customerBalance) . ' points'
                                    : 'Search for a customer to see their balance'
                            )
                            ->extraAttributes([
                                'class' => 'text-2xl font-bold text-blue-600'
                            ]),

                        Placeholder::make('transaction_count')
                            ->label('Total Transactions')
                            ->content(fn ($get, $livewire) =>
                                !empty($livewire->customerTransactions)
                                    ? count($livewire->customerTransactions) . ' transactions'
                                    : 'No transactions found'
                            ),
                    ])
                    ->columns(2)
                    ->visible(fn ($get, $livewire): bool => $livewire->customerBalance !== null),

                Actions::make([
                    Action::make('search_customer')
                        ->label('Search Customer')
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('info')
                        ->action('searchCustomer')
                        ->disabled(fn (Get $get): bool =>
                            empty($get('search_company_id')) ||
                            empty($get('search_customer_email'))
                        ),
                ])->alignEnd(),
            ]);
    }

    private static function getRedeemPointsTab($livewire, $companyOptions, $isHandler): Tabs\Tab
    {
        return Tabs\Tab::make('redeem_points')
            ->label('Redeem Points')
            ->icon('heroicon-o-minus-circle')
            ->schema([
                Section::make('Redemption Details')
                    ->schema([
                        Select::make('redeem_company_id')
                            ->label('Company')
                            ->options($companyOptions)
                            ->required()
                            ->disabled($isHandler && count($companyOptions) === 1)
                            ->helperText($isHandler ? 'You can only redeem points for your assigned companies' : null)
                            ->live()
                            ->afterStateUpdated(fn ($state) => $livewire->validateCompanyAccess($state) && $livewire->resetRedemptionData()),

                        TextInput::make('redeem_customer_email')
                            ->label('Customer Email')
                            ->email()
                            ->required(),

                        TextInput::make('redeem_points')
                            ->label('Points to Redeem')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->suffix('points')
                            ->helperText('Enter the number of points to redeem'),

                        Textarea::make('redemption_description')
                            ->label('Redemption Description')
                            ->placeholder('e.g., Free coffee, 10% discount, etc.')
                            ->required()
                            ->helperText('Describe what the customer is redeeming'),
                    ])
                    ->columns(2),

                Actions::make([
                    Action::make('generate_redemption_qr')
                        ->label('Generate Redemption QR')
                        ->icon('heroicon-o-qr-code')
                        ->color('warning')
                        ->action('generateRedemptionQr')
                        ->disabled(fn (Get $get): bool =>
                            empty($get('redeem_company_id')) ||
                            empty($get('redeem_customer_email')) ||
                            empty($get('redeem_points')) ||
                            empty($get('redemption_description'))
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Generate Redemption QR')
                        ->modalDescription('This will generate a QR code. Scanning it will confirm the point redemption.'),
                ])->alignEnd(),
            ]);
    }
}
