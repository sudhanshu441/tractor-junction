<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/** Local/staging driver: writes the SMS to the log instead of spending credits. */
class LogSmsGateway implements SmsGateway
{
    public function send(string $mobile, string $message, ?string $templateId = null): bool
    {
        Log::channel(config('kj.sms.log_channel', 'stack'))
            ->info('[SMS] to '.$mobile.' :: '.$message, ['template' => $templateId]);

        return true;
    }
}
