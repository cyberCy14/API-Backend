<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyProgram extends Model
{
    protected $table = 'loyaltyPrograms';
    protected $fillable = [
    'id',
    'company_id',
    'program_name',
    'description',
    'program_type',
    'instructions',
    'created_at',
    'updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function rules()
    {
        return $this->hasMany(LoyaltyProgramRule::class, 'loyalty_program_id');
    }
}
