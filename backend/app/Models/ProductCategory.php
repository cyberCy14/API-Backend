<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    protected $table = 'productCategory';
    protected $fillable = [
        'category_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function loyaltyProgramRules(): HasMany
    {
        return $this->hasMany(LoyaltyProgramRule::class);
    }

    public function productItems(): HasMany
    {
        return $this->hasMany(ProductItem::class);
    }
}
