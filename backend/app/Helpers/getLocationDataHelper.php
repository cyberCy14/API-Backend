<?php

  namespace App\Helpers;

use Exception;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node\Expr\Throw_;

use function Laravel\Prompts\error;

class getLocationDataHelper{

  protected static ?array $phLocations = null;
  protected static string $phLocationsPath = 'json/philippine_provinces_cities_municipalities_and_barangays_2019v2.json';

  protected static function loadData():array{

    if(self::$phLocations != null){
      return self::$phLocations;
    }

    try{
    if(!Storage::disk('local')->exists(self::$phLocationsPath)){
        throw new Exception("FAILED TO FIND JSON FILE AT" . Storage::path(self::$phLocationsPath));
    }

        $phLocationsJson = Storage::disk('local')->get(self::$phLocationsPath);
        self::$phLocations = json_decode($phLocationsJson, true);
      if(!is_array(self::$phLocations) || empty(self::$phLocations)){
        throw new Exception("Json File is Empty or is malformed");
      }

    }
    catch(Exception $e){
        self::$phLocations = [];
    }

    return self::$phLocations;
  }

  public static function getRegions():array{

    $data = self::loadData();
    return Collection::make($data['region_code'])->mapWithKeys(fn ($region, $code) => [$code => $region['region_name']])->all();

  }

    public static function getProvince(?string $regionCode):array{

    $data = self::loadData();
    if(empty($regionCode)){
      return [''];
    }
        return Collection::make($data['region_code'][$regionCode]['province_list'])->mapWithKeys(fn($province, $code)=>[$code => $code])->all();

    // return Collection::make($data['region_code'])
  }

    public static function getMunicipality(?string $regionCode, ?string $provinceName):array{

    $data = self::loadData();
    if(empty($regionCode) || empty($provinceName)){
      return [''];
    }
        return Collection::make($data['region_code'][$regionCode]['province_list'][$provinceName]['municipality_list'])->mapWithKeys(fn($municipality, $code)=> [$code=>$code])->all();

    // return Collection::make($data['region_code'])
  }

    public static function getBarangay(?string $regionCode, ?string $provinceName, ?string $municipality):array{

    $data = self::loadData();
    if(empty($regionCode) || empty($provinceName) || empty($municipality)){
      return [''];
    }
        return Collection::make($data['region_code'][$regionCode]['province_list'][$provinceName]['municipality_list'][$municipality]['barangay_list'])->mapWithKeys(fn($barangay)=>[$barangay=>$barangay])->all();

  }

}
