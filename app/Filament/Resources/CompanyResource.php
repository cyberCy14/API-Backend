<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Helpers\GetLocationDataHelper;
use App\Models\Company;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasRoleHelpers;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\ImageEditor;
use Filament\Tables\Columns\BadgeColumn;

class CompanyResource extends Resource
{
    use HasRoleHelpers; 
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Company Management';
    protected static ?string $navigationLabel = 'Companies';
    protected static ?int $navigationSort = 1;

    // Filament Shield handles permissions, so we only need data filtering

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();
        
        // If user is super_admin, show all companies
        if ($user->hasRole('super_admin')) {
            return $query;
        }
        
        // If user is handler, only show companies they belong to
        if ($user->hasRole('handler')) {
            return $query->whereHas('users', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }
        
        // Default: show no companies if role is not recognized
        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if current user can create companies
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('super_admin'); // Only super_admin can create companies
    }

    /**
     * Check if current user can edit a specific company record
     */
    public static function canEdit($record): bool
    {
        $user = Auth::user();
        
        if (!$user) return false;
        
        // Superadmin can edit any company
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        // Handler can edit companies they belong to (with restrictions)
        if ($user->hasRole('handler')) {
            return $user->companies->contains($record->id);
        }
        
        return false;
    }

    /**
     * Check if current user can delete a specific company record
     */
    public static function canDelete($record): bool
    {
        $user = Auth::user();
        
        if (!$user) return false;
        
        // Only super_admin can delete companies
        return $user->hasRole('super_admin');
    }

    public static function form(Forms\Form $form): Forms\Form
    {   
        $user = Auth::user();
        $isSuperadmin = $user && $user->hasRole('super_admin');
        $isHandler = $user && $user->hasRole('handler');
        
        // Debug: Let's see what roles the user actually has
        if ($user) {
            Log::info('Current user roles: ' . $user->roles->pluck('name')->join(', '));
            Log::info('Is super_admin: ' . ($isSuperadmin ? 'true' : 'false'));
            Log::info('Is handler: ' . ($isHandler ? 'true' : 'false'));
        }
        
        return $form->schema([
            Forms\Components\Section::make('Company Details')->schema([
                Forms\Components\TextInput::make('company_name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                    
                Forms\Components\TextInput::make('display_name')
                    ->maxLength(255)
                    ->helperText('Optional display name for the company'),
                
                Forms\Components\FileUpload::make('company_logo')
                    ->image()
                    ->imageCropAspectRatio('1:1')
                    ->maxSize(548)
                    ->directory('company_images')
                    ->disk('public')
                    ->visibility('public')
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '1:1',
                    ]),
                    
                Forms\Components\Select::make('business_type_id')
                    ->required()
                    ->relationship('businessType', 'type')
                    ->preload()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('type')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->visible($isSuperadmin), // Only super_admin can change business type
                    
                Forms\Components\Placeholder::make('business_type_display')
                    ->label('Business Type')
                    ->content(fn ($record) => $record?->businessType?->type ?? 'Not set')
                    ->visible($isHandler), // Handler sees read-only business type
                    
                Forms\Components\Select::make('user_id')
                    ->label('Handlers')
                    ->multiple()
                    ->preload()
                    ->relationship('users', 'name')
                    ->searchable()
                    ->visible(function() use ($user) {
                        // Double check - only show if user is NOT a handler
                        if (!$user) return false;
                        
                        // If user has handler role, hide this field completely
                        if ($user->hasRole('handler')) {
                            return false;
                        }
                        
                        // Show only if user has super_admin role
                        return $user->hasRole('super_admin');
                    }),
                    
                // Show current handlers as read-only for handlers
                Forms\Components\Placeholder::make('current_users_display')
                    ->label('Current Handlers')
                    ->content(function ($record) {
                        if (!$record || !$record->users) {
                            return 'No handlers assigned';
                        }
                        
                        $userNames = $record->users->pluck('name')->toArray();
                        return empty($userNames) ? 'No handlers assigned' : implode(', ', $userNames);
                    })
                    ->visible($isHandler), // Only show to handlers

                Forms\Components\Toggle::make('is_active')
                    ->label('Company Active')
                    ->default(true)
                    ->visible($isSuperadmin) // Only super_admin can activate/deactivate
                    ->helperText('Inactive companies will not appear in dropdowns'),
                    
                Forms\Components\Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn ($record) => $record?->is_active ? '✅ Active' : '❌ Inactive')
                    ->visible($isHandler), // Handler sees read-only status
            ])->columns(2),

            Forms\Components\Section::make('Contacts')->schema([
                Forms\Components\TextInput::make('telephone_contact_1')
                    ->label('Primary Phone')
                    ->required()
                    ->tel()
                    ->maxLength(20),
                    
                Forms\Components\TextInput::make('telephone_contact_2')
                    ->label('Secondary Phone')
                    ->tel()
                    ->maxLength(20),
                    
                Forms\Components\TextInput::make('email_contact_1')
                    ->label('Primary Email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('email_contact_2')
                    ->label('Secondary Email')
                    ->email()
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Address')->schema([
                Forms\Components\Select::make('region')
                    ->required()
                    ->options(GetLocationDataHelper::getRegions())
                    ->live()
                    ->afterStateUpdated(fn(Forms\Set $set) => collect(['province', 'city_municipality', 'barangay'])->each(fn($field) => $set($field, null))),
                    
                Forms\Components\Select::make('province')
                    ->required()
                    ->options(fn(Forms\Get $get) => GetLocationDataHelper::getProvinces($get('region')))
                    ->live()
                    ->afterStateUpdated(fn(Forms\Set $set) => collect(['city_municipality', 'barangay'])->each(fn($field) => $set($field, null))),
                    
                Forms\Components\Select::make('city_municipality')
                    ->required()
                    ->options(fn(Forms\Get $get) => GetLocationDataHelper::getMunicipalities($get('region'), $get('province')))
                    ->live()
                    ->afterStateUpdated(fn(Forms\Set $set) => $set('barangay', null)),
                    
                Forms\Components\Select::make('barangay')
                    ->required()
                    ->options(fn(Forms\Get $get) => GetLocationDataHelper::getBarangays($get('region'), $get('province'), $get('city_municipality')))
                    ->live(),
                    
                Forms\Components\TextInput::make('zipcode')
                    ->required()
                    ->numeric()
                    ->minValue(1000)
                    ->maxValue(9999)
                    ->length(4),
                    
                Forms\Components\TextInput::make('street')
                    ->required()
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Company Registration')->schema([
                Forms\Components\TextInput::make('business_registration_number')
                    ->label('Business Registration Number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                    
                Forms\Components\TextInput::make('tin_number')
                    ->label('TIN Number')
                    ->required()
                    ->numeric()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ])->columns(2)
            ->visible($isSuperadmin), // Only super_admin can view/edit registration details

            Forms\Components\Section::make('Company Registration (Read Only)')
                ->schema([
                    Forms\Components\Placeholder::make('registration_display')
                        ->label('Business Registration Number')
                        ->content(fn ($record) => $record?->business_registration_number ?? 'Not set'),
                        
                    Forms\Components\Placeholder::make('tin_display')
                        ->label('TIN Number')
                        ->content(fn ($record) => $record?->tin_number ?? 'Not set'),
                ])
                ->columns(2)
                ->visible($isHandler), // Handler sees read-only registration details
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('super_admin');
        
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('company_logo')
                    ->label('Logo')
                    ->size(40)
                    ->circular()
                    ->disk('public'),
                    
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                    
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Display Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\BadgeColumn::make('businessType.type')
                    ->label('Business Type')
                    ->sortable(),
                    
                Tables\Columns\BadgeColumn::make('users_count')
                    ->label('Handlers')
                    ->counts('users')
                    ->color('success'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->visible($isSuperadmin),
                    
                Tables\Columns\TextColumn::make('email_contact_1')
                    ->label('Primary Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('telephone_contact_1')
                    ->label('Primary Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_type_id')
                    ->label('Business Type')
                    ->relationship('businessType', 'type'),
                    
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->visible($isSuperadmin),
                    
                Tables\Filters\Filter::make('has_users')
                    ->label('Has Handlers')
                    ->query(fn (Builder $query): Builder => $query->has('users'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record))
                    ->before(function ($record) {
                        // Check if company has users
                        if ($record->users()->count() > 0) {
                            Notification::make()
                                ->title('Cannot Delete')
                                ->body('Company has handlers assigned. Remove all handlers first.')
                                ->danger()
                                ->send();
                            return false;
                        }
                        
                        // Check if company has loyalty programs
                        if ($record->loyaltyPrograms()->count() > 0) {
                            Notification::make()
                                ->title('Cannot Delete')
                                ->body('Company has loyalty programs. Remove all loyalty programs first.')
                                ->danger()
                                ->send();
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible($isSuperadmin)
                    ->before(function ($records) {
                        foreach ($records as $record) {
                            if ($record->users()->count() > 0 || $record->loyaltyPrograms()->count() > 0) {
                                Notification::make()
                                    ->title('Cannot Delete')
                                    ->body('Some companies have handlers or loyalty programs assigned.')
                                    ->danger()
                                    ->send();
                                return false;
                            }
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
            'view' => Pages\ViewCompany::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        
        if (!$user) return null;
        
        // FIXED: Use the trait methods with correct names
        if ((new static)->isSuperAdmin($user)) {
            $total = static::getModel()::count();
            $active = static::getModel()::where('is_active', true)->count();
            return "{$active}/{$total}";
        }
        
        if ((new static)->isHandler($user)) {
            return (string) $user->companies()->count();
        }
        
        return null;
    }

    /**
     * Get the navigation label for the resource
     */
    public static function getNavigationLabel(): string
    {
        $user = Auth::user();
        
        // FIXED: Use the trait method with correct name
        if ($user && (new static)->isHandler($user)) {
            return 'My Companies';
        }
        
        return 'Companies';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
    
        if (!$user) {
            return false;
        }
    
        // Super admin always sees everything
        if ($user->hasRole('super_admin')) {
            return true;
        }
    
        // Handler can only see resources tied to their company
        if ($user->hasRole('handler')) {
            return true; // They can access, but filtering is applied in getEloquentQuery()
        }
    
        return false;
    }
    
}