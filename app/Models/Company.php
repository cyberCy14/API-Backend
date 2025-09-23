<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CustomerPoint;
use App\Models\User;
use App\Models\BusinessType;

class Company extends Model
{
    use HasFactory;
    
    // fix: correct table property name
    protected $table = 'companies';

    protected $fillable = [
        'company_name',
        'display_name',
        'company_logo',
        'business_type_id',
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
        'tin_number' => 'encrypted',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'company_user')->withTimestamps();
    }

    public function businessType(): BelongsTo
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }
    
    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * All customer points for this company (all users).
     */
    public function customerPoints(): HasMany
    {
        return $this->hasMany(CustomerPoint::class, 'company_id', 'id');
    }

    /**
     * Customer points for a specific customer.
     * Usage: $company->customerPointsFor($customerId)->get()
     */
    public function customerPointsFor($customerId): HasMany
    {
        return $this->customerPoints()->where('customer_id', $customerId);
    }
}
