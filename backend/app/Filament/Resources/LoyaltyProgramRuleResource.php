<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyProgramRuleResource\Pages;
use App\Filament\Resources\LoyaltyProgramRuleResource\RelationManagers;
use App\Models\LoyaltyProgramRule;
use App\Models\ProductCategory;
use App\Models\ProductItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illumninate\Database\Eloquent\Relations\Relation;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use App\Filament\Widgets\LoyaltyRuleStatsWidget;

class LoyaltyProgramRuleResource extends Resource
{
    protected static ?string $model = LoyaltyProgramRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Loyalty Program Rules Management';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'rule_name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                ->schema([
                    Forms\Components\Select::make('loyalty_program_id')
                    ->label('Loyalty Program')
                    ->relationship('loyaltyProgram', 'program_name')
                    ->required()
                    ->searchable()
                    ->preload(),

                    Forms\Components\TextInput::make('rule_name')
                        ->label('Rule Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $context, $state, Forms\Set $set) {
                            if ($context === 'create') {
                                $set('rule_name', ucfirst($state));
                            }
                        }),

                    Forms\Components\Select::make('rule_type')
                        ->options([
                            'purchase_based' => 'Purchase Based',
                            'birthday' => 'Birthday',
                            'referral_bonus' => 'Referral Bonus',
                        ])
                        ->required()
                        ->live()
                        ->afterStateUpdated((fn (Forms\Set $set) => $set ('points_earned', null))),
                        ])
                        ->columns(2),
                    
                Forms\Components\Section::make('Points Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('points_earned')
                            ->label('Points Earned')
                            ->integer()
                            ->minValue(0)
                            ->required()
                            ->suffix('points')
                            ->helperText('Points earned for this rule.'),
                            
                        Forms\Components\TextInput::make('amount_per_point')
                            ->label('Amount per Point')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('PHP')
                            ->visible (fn (Get $get): bool => $get('rule_type') === 'purchase_based')
                            ->helperText('Amount in PHP for each point earned.'),

                        Forms\Components\TextInput::make('min_purchase_amount')
                            ->label('Minimum Purchase Amount')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->prefix('PHP')
                            ->visible (fn (Get $get): bool => $get('rule_type') === 'purchase_based')
                            ->helperText('Minimum purchase amount required to earn points.'),
                            ])
                            ->columns(3),

                Forms\Components\Section::make('Product Configuration')
                    ->schema([
                        Forms\Components\Select::make('product_category_id')
                            ->label('Product Category ID')
                            ->relationship('productCategory', 'category_name')
                            ->searchable()
                            ->preload()
                            ->nullable(),

                        Forms\Components\Select::make('product_item_id')
                            ->label('Product Item ID')
                            ->relationship('productItem', 'item_name')
                            ->searchable()
                            ->nullable(),
                            ])
                            ->columns(2)
                            ->visible(fn (Get $get): bool => $get('rule_type') === 'purchase_based'),
                Forms\Components\Section::make('Activity Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true),

                        Forms\Components\DatePicker::make('active_from_date')
                            ->label('Active From Date')
                            ->nullable()
                            ->default(now()),

                        Forms\Components\DatePicker::make('active_to_date')
                            ->label('Active To Date')
                            ->nullable()
                            ->afterOrEqual('active_from_date'),

                        Forms\Components\TextInput::make('usage_limit')
                            ->label('Usage Limit')
                            ->integer()
                            ->minValue(1)
                            ->nullable()
                            ->helperText('Maximum number of times this rule can be used.(optional)'),
                            ])
                            ->columns(2),     
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('loyaltyProgram.program_name')
                    ->label('Loyalty Program')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('rule_name')
                    ->label('Rule Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('rule_type')
                    ->label('Rule Type')
                    ->colors([
                        'purchase_based' => 'primary',
                        'birthday' => 'success',
                        'referral_bonus' => 'warning',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'purchase_based' => 'Purchase Based',
                        'birthday' => 'Birthday',
                        'referral_bonus' => 'Referral Bonus',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('points_earned')
                    ->label('Points Earned')
                    ->suffix('points')
                    ->alignCenter()
                    ->sortable()
                    ->numeric(),

                Tables\Columns\TextColumn::make('amount_per_point')
                    ->label('Amount per Point')
                    ->prefix('PHP')
                    ->alignCenter()
                    ->placeholder('N/A')
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\TextColumn::make('min_purchase_amount')
                    ->label('Minimum Purchase Amount')
                    ->prefix('PHP')
                    ->alignCenter()
                    ->placeholder('N/A')
                    ->numeric(decimalPlaces: 2),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('active_from_date')
                    ->label('Active From Date')
                    ->date('M d, Y')
                    ->placeholder('N/A')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('active_to_date')
                    ->label('Active To Date')
                    ->date('M d, Y')
                    ->placeholder('N/A')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('usage_limit') 
                    ->label('Usage Limit')
                    ->placeholder('Unlimited')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                Tables\Filters\SelectFilter::make('loyalty_program_id')
                    ->label('Loyalty Program')
                    ->relationship('loyaltyProgram', 'program_name')
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\SelectFilter::make('rule_type')
                    ->label('Rule Type')
                    ->options([
                        'purchase_based' => 'Purchase Based',
                        'birthday' => 'Birthday',
                        'referral_bonus' => 'Referral Bonus',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
                
                ], layout: FiltersLayout::AboveContent)
                ->filtersFormColumns(4)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function (LoyaltyProgramRule $record) {
                            $newRule = $record->replicate();
                            $newRule->rule_name = $record->rule_name . ' (Copy)';
                            $newRule->is_active = false;
                            $newRule->save();
                            
                            Notification::make()
                                ->title('Rule duplicated successfully')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn (LoyaltyProgramRule $record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn (LoyaltyProgramRule $record) => $record->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                        ->color(fn (LoyaltyProgramRule $record) => $record->is_active ? 'warning' : 'success')
                        ->action(function (LoyaltyProgramRule $record) {
                            $record->update(['is_active' => !$record->is_active]);
                            
                            Notification::make()
                                ->title('Rule status updated')
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => true]);
                            
                            Notification::make()
                                ->title('Rules activated successfully')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->title('Rules deactivated successfully')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            LoyaltyRuleStatsWidget::class,
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyProgramRules::route('/'),
            'create' => Pages\CreateLoyaltyProgramRule::route('/create'),
            'edit' => Pages\EditLoyaltyProgramRule::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', true)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }
}
