<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends Model
{
    use HasFactory;

    protected $table = 'profile';

    protected $fillable = [
        'user_id',
        'name',
        'phone',
        'address', 
        'place',
        'dob',
        'gender',
        'image'
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
}
