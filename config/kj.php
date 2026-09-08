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

    'documents' => [
        'audit_channel' => env('KJ_DOCUMENT_AUDIT_CHANNEL', 'stack'),
        'max_size_kb' => (int) env('KJ_DOCUMENT_MAX_KB', 5120),
        'link_ttl_minutes' => 5,
    ],

    'finance' => [
        'default_interest_rate' => (float) env('KJ_DEFAULT_INTEREST_RATE', 11.5),
        'default_down_payment_percent' => (int) env('KJ_DEFAULT_DOWN_PAYMENT', 20),
        'min_tenure_months' => 12,
        'max_tenure_months' => 84,
    ],

    'payments' => [
        'driver' => env('PAYMENT_DRIVER', 'log'),
        'currency' => 'INR',
        'razorpay' => [
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
        ],
    ],

    'locales' => ['en' => 'English', 'hi' => 'हिन्दी'],
];
