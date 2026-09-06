<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $table = 'user_profiles';

    protected $fillable = [
        'user_id',
        'date_of_birth',
        'gender',
        'land_holding_acres',
        'farming_type',
        'crops',
        'address_line',
        'pincode',
        'preferences',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'land_holding_acres' => 'decimal:2',
            'crops' => 'array',
            'preferences' => 'array',
        ];
    }
}
