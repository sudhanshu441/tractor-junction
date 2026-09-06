<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsedListingStatusLog extends Model
{
    use HasFactory;

    protected $table = 'used_listing_status_logs';

    protected $fillable = [
        'used_listing_id',
        'from_status',
        'to_status',
        'changed_by',
        'remarks',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
