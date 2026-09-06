<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91SmsGateway implements SmsGateway
{
    public function __construct(
        private readonly string $authKey,
        private readonly string $senderId,
    ) {}

    public function send(string $mobile, string $message, ?string $templateId = null): bool
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['authkey' => $this->authKey])
                ->post('https://control.msg91.com/api/v5/flow/', [
                    'template_id' => $templateId ?: config('kj.sms.msg91.otp_template_id'),
                    'sender' => $this->senderId,
                    'recipients' => [['mobiles' => '91'.$mobile, 'message' => $message]],
                ]);

            if ($response->failed()) {
                Log::error('MSG91 send failed', ['status' => $response->status(), 'body' => $response->body()]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // Never let a gateway outage break the user's request; the caller decides what to show.
            Log::error('MSG91 exception: '.$e->getMessage());

            return false;
        }
    }
}
