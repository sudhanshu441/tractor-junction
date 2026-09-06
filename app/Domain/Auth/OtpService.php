<?php

namespace App\Domain\Auth;

use App\Models\OtpVerification;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    public function __construct(private readonly SmsManager $sms) {}

    /**
     * Issue an OTP for a mobile number.
     *
     * @return array{sent: bool, reason?: string, retry_after?: int}
     */
    public function send(string $mobile, string $purpose = 'login', ?string $ip = null): array
    {
        $mobileKey = 'otp:mobile:'.$mobile;
        $ipKey = 'otp:ip:'.($ip ?: 'unknown');

        if (RateLimiter::tooManyAttempts($mobileKey, config('kj.otp.rate_per_hour'))) {
            return ['sent' => false, 'reason' => 'too_many_for_mobile', 'retry_after' => RateLimiter::availableIn($mobileKey)];
        }

        if (RateLimiter::tooManyAttempts($ipKey, config('kj.otp.ip_rate_per_hour'))) {
            return ['sent' => false, 'reason' => 'too_many_for_ip', 'retry_after' => RateLimiter::availableIn($ipKey)];
        }

        $code = $this->generateCode();

        OtpVerification::create([
            'mobile' => $mobile,
            'otp_hash' => Hash::make($code),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(config('kj.otp.ttl_minutes')),
            'ip' => $ip,
        ]);

        RateLimiter::hit($mobileKey, 3600);
        RateLimiter::hit($ipKey, 3600);

        $message = "{$code} is your Krishi Junction verification code. Valid for "
            .config('kj.otp.ttl_minutes').' minutes. Do not share it with anyone.';

        $this->sms->send($mobile, $message, config('kj.sms.msg91.otp_template_id'));

        if (config('kj.otp.debug_log')) {
            Log::info("[OTP DEBUG] {$mobile} => {$code}");
        }

        return ['sent' => true];
    }

    /**
     * Verify a submitted code.
     *
     * @return array{verified: bool, reason?: string}
     */
    public function verify(string $mobile, string $code, string $purpose = 'login'): array
    {
        $otp = OtpVerification::usable($mobile, $purpose)->first();

        if (! $otp) {
            return ['verified' => false, 'reason' => 'expired_or_missing'];
        }

        if ($otp->attempts >= config('kj.otp.max_attempts')) {
            return ['verified' => false, 'reason' => 'too_many_attempts'];
        }

        if (! Hash::check($code, $otp->otp_hash)) {
            $otp->increment('attempts');

            return ['verified' => false, 'reason' => 'incorrect'];
        }

        $otp->update(['verified_at' => now()]);

        // One code, one use: kill any other live codes for this mobile+purpose.
        OtpVerification::usable($mobile, $purpose)->where('id', '!=', $otp->id)
            ->update(['expires_at' => now()]);

        return ['verified' => true];
    }

    private function generateCode(): string
    {
        $length = max(4, min(8, (int) config('kj.otp.length', 6)));

        return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
    }
}
