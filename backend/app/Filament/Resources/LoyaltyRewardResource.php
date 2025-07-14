<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyRewardResource\Pages;
use App\Filament\Resources\LoyaltyRewardResource\RelationManagers;
use App\Models\LoyaltyReward;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PHPUnit\Event\Code\Test;

class LoyaltyRewardResource extends Resource
{
    protected static ?string $model = LoyaltyReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('reward_name')->required(),
                // Select::make('loyalty_program_id')
                // ->relationship('loyaltyProgram', 'program_name')
                // ->searchable()
                // ->required(),
                Select::make('reward_type')->options(['voucher','discount'])->required(),
                TextInput::make('point_cost')->numeric()->required(),
                TextInput::make('discount_value')->numeric()
                ->default(0)
                ->minValue(0)
                ->nullable(),
                TextInput::make('discount_precentage')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->maxValue(100)
                ->nullable(),
                TextInput::make('voucher_code')->nullable(),
                Toggle::make('is_active')->default(true)->required(),
                TextInput::make('max_redemption_rate')->numeric()->nullable(),
                TextInput::make('expiration_days')->numeric()->nullable(),


            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reward_name')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyRewards::route('/'),
            'create' => Pages\CreateLoyaltyReward::route('/create'),
            'edit' => Pages\EditLoyaltyReward::route('/{record}/edit'),
        ];
    }
}
