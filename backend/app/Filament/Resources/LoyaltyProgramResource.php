<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyProgramResource\Pages;
use App\Filament\Resources\LoyaltyProgramResource\RelationManagers;
use App\Models\LoyaltyProgram;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;

class LoyaltyProgramResource extends Resource
{
    protected static ?string $model = LoyaltyProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                TextInput::make('program_name')
                ->required()
                ->maxLength(255),

                Textarea::make('description')
                ->autosize()
                ->required(),

                TextInput::make('program_type')
                ->default('point_based'),

                Select::make('company_id')->relationship('company', 'company_name')->searchable()->required(),

                Toggle::make('is_active')->default(true),
                DatePicker::make('start_date')->default(now())->required(),
                DatePicker::make('end_date')->afterOrEqual('start_date')->nullable(),
                Textarea::make('instruction')->autosize(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('program_name')
                
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
            'index' => Pages\ListLoyaltyPrograms::route('/'),
            'create' => Pages\CreateLoyaltyProgram::route('/create'),
            'edit' => Pages\EditLoyaltyProgram::route('/{record}/edit'),
        ];
    }
}
