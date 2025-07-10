<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use Exception;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use PhpParser\Node\Stmt\TryCatch;

use function Pest\Laravel\get;
use function Pest\Laravel\json;
use function PHPUnit\Framework\returnCallback;

use App\Helpers\getLocationDataHelper;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        $busniessTypesPath = 'businessTypes.json';

        $businessTypes = [];
        $phLocations = [];

        try{
            $businessTypesJson = Storage::disk('local')->get($busniessTypesPath); 
            $businessTypes = json_decode($businessTypesJson, true);

        }
        catch(Exception $e){
            echo $e;
        }
// dd(getLocationDataHelper::getRegions());
        $businessTypesOptions = Collection::make($businessTypes)->pluck('name', 'id')->toArray();

        return $form

            ->schema([
                
                    Forms\Components\TextInput::make('company_name')
                    ->required()
                    ->maxLength(255)
                    ->label('Company_Name'),
                    Forms\Components\TextInput::make('display_name')
                    ->label('Display Name')
                    ->maxLength(255),

                    Forms\Components\FileUpload::make('company_logo')
                    ->required()
                    ->label('Company Logo')
                    ->disk('public')
                    ->previewable(true)
                    ->image(),

                    Forms\Components\Select::make('business_type')
                    ->required()
                    ->label('Business Type')
                    ->options($businessTypesOptions)
                    ->preload()
                    ->searchable(),

                    Forms\Components\TextInput::make('telephone_contact_1')
                    ->required()
                    ->numeric()
                    ->maxLength(255)
                    ->label('Main Telephone Contact Number'),

                    Forms\Components\TextInput::make('telephone_contact_2')
                    ->maxLength(255)
                    ->numeric()
                    ->label('Telephone Contact Number'),

                    Forms\Components\TextInput::make('email_contact_1')
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->label('Main Email Contact Address'),

                    Forms\Components\TextInput::make('email_contact_2')
                    ->maxLength(255)
                    ->email()
                    ->label('Email Contact Address'),

                    Forms\Components\Select::make('region')
                    ->required()
                    ->label('Region')
                    ->options(getLocationDataHelper::getRegions())
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function(Set $set){
                        $set('province', null);
                        $set('city_municipality', null);
                        $set('barangay', null);
                    })
                    ->searchable(),

                    Forms\Components\Select::make('province')
                    ->required()
                    ->label('Province')
                    ->options(function(Get $get){
                        $regionCode = $get('region');
                        return getLocationDataHelper::getProvince($regionCode);
                    })
                    ->preload()
                    ->live()
                    ->searchable()
                    ->afterStateUpdated(function(Set $set){
                        $set('city_municipality', null);
                        $set('barangay', null);
                    }),

                    Forms\Components\Select::make('city_municipality')
                    ->required()
                    ->label('City/Municipality')
                    ->options(function(Get $get){
                        $regionCode = $get('region');
                        $provinceName = $get('province');
                        return getLocationDataHelper::getMunicipality($regionCode, $provinceName);
                    })
                    ->preload()
                    ->live()
                    ->searchable(),

                    Forms\Components\Select::make('barangay')
                    ->required()
                    ->label('Barangay')
                    ->options(function(Get $get){
                        $regionCode = $get('region');
                        $provinceName = $get('province');
                        $municipality = $get('city_municipality');
                        return getLocationDataHelper::getBarangay($regionCode, $provinceName, $municipality);
                    })
                    ->preload()
                    ->live()
                    ->searchable(),

                    Forms\Components\TextInput::make('zipcode')
                    ->required()
                    ->numeric()
                    ->maxLength(255)
                    ->label('Zip Code'),

                    Forms\Components\TextInput::make('street')
                    ->required()
                    ->maxLength(255)
                    ->label('Street'),

                    Forms\Components\TextInput::make('business_registration_number')
                    ->required()
                    ->maxLength(255)
                    ->label('Business Registration Number'),

                    Forms\Components\TextInput::make('tin_number')
                    ->required()
                    ->numeric()
                    ->maxLength(255)
                    ->label('TIN Number'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company_name')
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
