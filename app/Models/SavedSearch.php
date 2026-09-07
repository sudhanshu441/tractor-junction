<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSearch extends Model
{
    use HasFactory;

    protected $table = 'saved_searches';

    protected $fillable = [
        'user_id',
        'name',
        'context',
        'filters',
        'alert_enabled',
        'last_alert_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'alert_enabled' => 'boolean',
            'last_alert_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
