<?php

/*
|--------------------------------------------------------------------------
| Krishi Junction application settings
|--------------------------------------------------------------------------
| Business rules that operations may need to tune without a code change get
| a matching row in the `settings` table; the values here are the defaults
| and the ceiling for anything security-sensitive (OTP limits, SLAs).
*/

return [
    'brand' => [
        'name' => 'Krishi Junction',
        'legal_name' => 'Krishi Junction',
        'support_mobile' => env('KJ_SUPPORT_MOBILE', ''),
        'support_email' => env('KJ_SUPPORT_EMAIL', 'support@krishijunction.com'),
    ],

    'otp' => [
        'length' => (int) env('KJ_OTP_LENGTH', 6),
        'ttl_minutes' => (int) env('KJ_OTP_TTL_MINUTES', 10),
        'max_attempts' => (int) env('KJ_OTP_MAX_ATTEMPTS', 5),
        'rate_per_hour' => (int) env('KJ_OTP_RATE_PER_HOUR', 5),
        'ip_rate_per_hour' => (int) env('KJ_OTP_IP_RATE_PER_HOUR', 20),
        // In local/staging the code is written to the log so testers can read it.
        'debug_log' => (bool) env('KJ_OTP_DEBUG_LOG', false),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
        'log_channel' => env('SMS_LOG_CHANNEL', 'stack'),
        'msg91' => [
            'auth_key' => env('MSG91_AUTH_KEY'),
            'sender_id' => env('MSG91_SENDER_ID', 'KRISHI'),
            'otp_template_id' => env('MSG91_OTP_TEMPLATE_ID'),
        ],
    ],

    'listings' => [
        'expiry_days' => (int) env('KJ_LISTING_EXPIRY_DAYS', 60),
        'reminder_days_before' => 7,
        'min_photos' => 4,
        'max_photos' => 12,
        'moderation_sla_hours' => 6,
    ],

    'leads' => [
        'response_sla_minutes' => 120,
        'duplicate_window_days' => 7,
    ],

    'locales' => ['en' => 'English', 'hi' => 'हिन्दी'],
];
