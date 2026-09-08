<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\OtpService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mobile app authentication — the same OTP flow the website uses.
 *
 * A password endpoint is deliberately absent: farmers sign in with a mobile
 * number and a code, and adding a second credential would only add a second
 * thing to steal.
 */
class AuthController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function sendOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
        ]);

        $result = $this->otp->send($data['mobile'], 'login', $request->ip());

        if (! $result['sent']) {
            return response()->json([
                'status' => 'error',
                'message' => __('Too many codes requested. Please try again shortly.'),
            ], 429);
        }

        return response()->json([
            'status' => 'ok',
            'message' => __('Code sent to :mobile.', ['mobile' => $data['mobile']]),
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'otp' => ['required', 'digits_between:4,8'],
            'name' => ['nullable', 'string', 'max:100'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $verification = $this->otp->verify($data['mobile'], $data['otp'], 'login');

        if (! $verification['verified']) {
            return response()->json([
                'status' => 'error',
                'message' => match ($verification['reason']) {
                    'incorrect' => __('That code is not correct.'),
                    'too_many_attempts' => __('Too many wrong attempts. Request a new code.'),
                    default => __('That code has expired. Request a new one.'),
                },
            ], 422);
        }

        $user = User::firstOrCreate(
            ['mobile' => $data['mobile']],
            [
                'name' => $data['name'] ?? null ?: __('Krishi Junction user'),
                'user_type' => 'customer',
                'is_active' => true,
                'mobile_verified_at' => now(),
            ],
        );

        if (! $user->is_active) {
            return response()->json(['status' => 'error', 'message' => __('This account is blocked.')], 403);
        }

        if (! $user->hasRole('customer')) {
            $user->assignRole('customer');
        }

        $user->forceFill(['mobile_verified_at' => $user->mobile_verified_at ?? now()])->save();

        // One token per named device, so signing in again on the same phone
        // replaces the old token rather than leaving it valid forever.
        $user->tokens()->where('name', $data['device_name'])->delete();

        return response()->json([
            'status' => 'ok',
            'message' => __('Signed in.'),
            'data' => [
                'token' => $user->createToken($data['device_name'], ['*'], now()->addDays(90))->plainTextToken,
                'user' => $this->profile($user),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['status' => 'ok', 'data' => $this->profile($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['status' => 'ok', 'message' => __('Signed out.')]);
    }

    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'type' => $user->user_type,
            'roles' => $user->getRoleNames(),
            'locale' => $user->locale,
        ];
    }
}
