<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $table = 'notification_templates';

    protected $fillable = [
        'event_key',
        'name',
        'channels',
        'sms_body',
        'email_subject',
        'email_body',
        'push_title',
        'push_body',
        'whatsapp_template_id',
        'variables',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
