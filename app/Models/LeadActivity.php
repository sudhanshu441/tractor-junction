<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadActivity extends Model
{
    use HasFactory;

    protected $table = 'lead_activities';

    protected $fillable = [
        'lead_id',
        'user_id',
        'activity',
        'from_status',
        'to_status',
        'description',
        'call_duration_seconds',
        'call_recording_url',
    ];

    protected function casts(): array
    {
        return [

        ];
    }
}
