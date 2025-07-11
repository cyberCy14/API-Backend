<?php

namespace App\Filament\Resources\LoyaltyProgramRuleResource\Pages;

use App\Filament\Resources\LoyaltyProgramRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;

class ViewLoyaltyProgramRule extends ViewRecord
{
    protected static string $resource = LoyaltyProgramRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('duplicate')
                ->label('Duplicate Rule')
                ->icon('heroicon-o-document-duplicate')
                ->action(function () {
                    $newRule = $this->record->replicate();
                    $newRule->rule_name = $this->record->rule_name . ' (Copy)';
                    $newRule->is_active = false;
                    $newRule->save();
                    
                    $this->redirect(static::$resource::getUrl('edit', ['record' => $newRule]));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Rule Information')
                    ->schema([
                        TextEntry::make('loyaltyProgram.program_name')
                            ->label('Loyalty Program'),
                        TextEntry::make('rule_name')
                            ->label('Rule Name'),
                        TextEntry::make('rule_type')
                            ->label('Rule Type')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'purchase_based' => 'Purchase Based',
                                'birthday' => 'Birthday Bonus',
                                'referral_bonus' => 'Referral Bonus',
                                'first_purchase' => 'First Purchase',
                                'milestone' => 'Milestone Achievement',
                                'seasonal' => 'Seasonal Promotion',
                                default => $state,
                            }),
                        IconEntry::make('is_active')
                            ->label('Status')
                            ->boolean(),
                    ])
                    ->columns(2),
                
                Section::make('Points & Rewards')
                    ->schema([
                        TextEntry::make('points_earned')
                            ->label('Points Earned')
                            ->suffix(' points'),
                        TextEntry::make('amount_per_point')
                            ->label('Amount per Point')
                            ->prefix('$')
                            ->placeholder('N/A'),
                        TextEntry::make('min_purchase_amount')
                            ->label('Minimum Purchase')
                            ->prefix('$')
                            ->placeholder('No minimum'),
                        TextEntry::make('usage_limit')
                            ->label('Usage Limit')
                            ->placeholder('Unlimited'),
                    ])
                    ->columns(2),
                
                Section::make('Activation Period')
                    ->schema([
                        TextEntry::make('active_from_date')
                            ->label('Active From')
                            ->date('F j, Y')
                            ->placeholder('Immediately'),
                        TextEntry::make('active_to_date')
                            ->label('Active Until')
                            ->date('F j, Y')
                            ->placeholder('No expiration'),
                    ])
                    ->columns(2),
                
                Section::make('Product Restrictions')
                    ->schema([
                        TextEntry::make('productCategory.name')
                            ->label('Product Category')
                            ->placeholder('All categories'),
                        TextEntry::make('productItem.name')
                            ->label('Specific Product')
                            ->placeholder('All products'),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record->rule_type === 'purchase_based'),
            ]);
    }
}