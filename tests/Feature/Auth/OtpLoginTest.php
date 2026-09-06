<?php

namespace Tests\Feature\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        RateLimiter::clear('otp:mobile:9876543210');
    }

    public function test_it_issues_an_otp_for_a_valid_mobile(): void
    {
        $this->postJson(route('ajax.otp.send'), ['mobile' => '9876543210'])
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseCount('otp_verifications', 1);
        // The code itself is never stored in plain text.
        $this->assertNotSame('', OtpVerification::first()->otp_hash);
    }

    public function test_it_rejects_a_malformed_mobile(): void
    {
        $this->postJson(route('ajax.otp.send'), ['mobile' => '12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mobile');

        // Indian mobiles never start below 6.
        $this->postJson(route('ajax.otp.send'), ['mobile' => '1234567890'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mobile');
    }

    public function test_verifying_a_correct_code_creates_and_logs_in_a_customer(): void
    {
        OtpVerification::create([
            'mobile' => '9876543210',
            'otp_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson(route('ajax.otp.verify'), [
            'mobile' => '9876543210',
            'otp' => '123456',
            'name' => 'Ramesh Kumar',
        ])->assertOk()->assertJsonPath('status', 'ok');

        $user = User::where('mobile', '9876543210')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('customer', $user->user_type);
        $this->assertTrue($user->hasRole('customer'));
        $this->assertNotNull($user->mobile_verified_at);
    }

    public function test_an_incorrect_code_is_rejected_and_counted(): void
    {
        $otp = OtpVerification::create([
            'mobile' => '9876543210',
            'otp_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson(route('ajax.otp.verify'), ['mobile' => '9876543210', 'otp' => '999999'])
            ->assertStatus(422);

        $this->assertGuest();
        $this->assertSame(1, $otp->fresh()->attempts);
    }

    public function test_an_expired_code_is_rejected(): void
    {
        OtpVerification::create([
            'mobile' => '9876543210',
            'otp_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson(route('ajax.otp.verify'), ['mobile' => '9876543210', 'otp' => '123456'])
            ->assertStatus(422);

        $this->assertGuest();
    }

    public function test_a_code_cannot_be_reused(): void
    {
        OtpVerification::create([
            'mobile' => '9876543210',
            'otp_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson(route('ajax.otp.verify'), ['mobile' => '9876543210', 'otp' => '123456'])->assertOk();

        $this->post(route('logout'));

        $this->postJson(route('ajax.otp.verify'), ['mobile' => '9876543210', 'otp' => '123456'])
            ->assertStatus(422);
    }

    public function test_otp_requests_are_rate_limited_per_mobile(): void
    {
        $limit = config('kj.otp.rate_per_hour');

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson(route('ajax.otp.send'), ['mobile' => '9876543210'])->assertOk();
        }

        $this->postJson(route('ajax.otp.send'), ['mobile' => '9876543210'])
            ->assertStatus(429)
            ->assertJsonPath('status', 'error');
    }

    public function test_a_blocked_user_cannot_log_in(): void
    {
        User::factory()->create(['mobile' => '9876543210', 'is_active' => false]);

        OtpVerification::create([
            'mobile' => '9876543210',
            'otp_hash' => Hash::make('123456'),
            'purpose' => 'login',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson(route('ajax.otp.verify'), ['mobile' => '9876543210', 'otp' => '123456'])
            ->assertStatus(403);

        $this->assertGuest();
    }
}
