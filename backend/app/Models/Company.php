<?php

namespace App\Models;

use Illuminate\Cache\Events\RetrievingKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    protected $company = 'companies';
    protected $fillable = [
        'company_name',
        'display_name',
        'company_logo',
        'business_type',
        'user_id',
        'telephone_contact_1',
        'telephone_contact_2',
        'email_contact_1',
        'email_contact_2',
        'barangay',
        'city_municipality',
        'province',
        'region',
        'zipcode',
        'country',
        'street',
        'business_registration_number',
        'tin_number',
        'currency_code',
    ];

    protected $casts = [
        'business_registration_number' => 'encrypted',
        'tin_number'=> 'encrypted',
    ];

    public function user():BelongsTo{
        return $this->belongsTo(User::class);
    }
}
