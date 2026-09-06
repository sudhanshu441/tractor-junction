<?php

namespace App\Services\Sms;

interface SmsGateway
{
    /**
     * Send a transactional SMS.
     *
     * @param  string  $mobile  10-digit Indian mobile, no country code
     * @return bool whether the gateway accepted the message
     */
    public function send(string $mobile, string $message, ?string $templateId = null): bool;
}
