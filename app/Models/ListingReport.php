<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListingReport extends Model
{
    use HasFactory;

    protected $table = 'listing_reports';

    protected $fillable = [
        'used_listing_id',
        'reported_by',
        'reason',
        'details',
        'status',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
