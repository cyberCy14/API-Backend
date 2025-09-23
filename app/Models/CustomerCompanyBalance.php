<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Company;

class CustomerCompanyBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'company_id',
        'total_balance',
    ];


    public function customerPoint(){
        return $this->belongsTo(CustomerPoint::class);
    }
    public function company(){
        return $this->belongsTo(Company::class);
    }
    
}
