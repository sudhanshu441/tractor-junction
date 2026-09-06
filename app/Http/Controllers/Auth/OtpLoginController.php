<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auth\OtpService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OtpLoginController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function show(): View
    {
        return view('auth.login');
    }

    /** AJAX: issue an OTP. */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
        ], [
            'mobile.regex' => __('Enter a valid 10-digit Indian mobile number.'),
        ]);

        $result = $this->otp->send($data['mobile'], 'login', $request->ip());

        if (! $result['sent']) {
            return response()->json([
                'status' => 'error',
                'message' => match ($result['reason']) {
                    'too_many_for_mobile' => __('Too many codes requested. Try again in :min minute(s).', [
                        'min' => (int) ceil(($result['retry_after'] ?? 60) / 60),
                    ]),
                    default => __('Too many requests from this network. Please try again later.'),
                },
            ], 429);
        }

        return response()->json([
            'status' => 'ok',
            'message' => __('We have sent a code to :mobile.', ['mobile' => $data['mobile']]),
            'data' => ['expires_in' => config('kj.otp.ttl_minutes') * 60],
        ]);
    }

    /** Verify the code and log the user in, creating the account on first use. */
    public function verify(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'digits:10'],
            'otp' => ['required', 'digits_between:4,8'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $result = $this->otp->verify($data['mobile'], $data['otp'], 'login');

        if (! $result['verified']) {
            $message = match ($result['reason']) {
                'incorrect' => __('That code is not correct. Please check and try again.'),
                'too_many_attempts' => __('Too many wrong attempts. Request a new code.'),
                default => __('That code has expired. Request a new one.'),
            };

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 422);
            }

            throw ValidationException::withMessages(['otp' => $message]);
        }

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::withTrashed()->where('mobile', $data['mobile'])->first();

            if (! $user) {
                $user = User::create([
                    'name' => $data['name'] ?? null ?: __('Krishi user'),
                    'mobile' => $data['mobile'],
                    'mobile_verified_at' => now(),
                    'user_type' => 'customer',
                    'is_active' => true,
                ]);
                $user->assignRole('customer');
            }

            $user->forceFill([
                'mobile_verified_at' => $user->mobile_verified_at ?? now(),
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            return $user;
        });

        if ($user->trashed() || ! $user->is_active) {
            $message = __('This account is not active. Please contact support.');

            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => $message], 403);
            }

            throw ValidationException::withMessages(['mobile' => $message]);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $target = $this->homeFor($user);

        if ($request->expectsJson()) {
            return response()->json(['status' => 'ok', 'data' => ['redirect' => $target]]);
        }

        return redirect()->intended($target);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function homeFor(User $user): string
    {
        return match ($user->user_type) {
            'staff' => route('admin.dashboard'),
            'dealer' => route('dealer.dashboard'),
            default => route('account.dashboard'),
        };
    }
}
