<?php

namespace App\Services\Sms;

use App\Models\NotificationLog;

/** Thin façade so callers never care which gateway is configured. */
class SmsManager
{
    public function __construct(private readonly SmsGateway $gateway) {}

    public function send(string $mobile, string $message, ?string $templateId = null, ?int $userId = null): bool
    {
        $ok = $this->gateway->send($mobile, $message, $templateId);

        NotificationLog::create([
            'user_id' => $userId,
            'channel' => 'sms',
            'recipient' => $mobile,
            'payload' => $message,
            'status' => $ok ? 'sent' : 'failed',
            'sent_at' => $ok ? now() : null,
        ]);

        return $ok;
    }
}
