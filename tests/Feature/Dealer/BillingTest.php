<?php

namespace Tests\Feature\Dealer;

use App\Domain\Billing\Services\BillingService;
use App\Domain\Dealer\Services\DealerService;
use App\Models\Dealer;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\UsedListing;
use App\Models\User;
use App\Services\Payment\PaymentGateway;
use Database\Seeders\FinanceMasterSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Money in. The rule under test throughout is that nothing is granted until a
 * verified callback marks the payment paid.
 */
class BillingTest extends TestCase
{
    use RefreshDatabase;

    private BillingService $billing;

    private Dealer $dealer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, NotificationTemplateSeeder::class,
            MarketplaceMasterSeeder::class, FinanceMasterSeeder::class,
        ]);

        $this->billing = app(BillingService::class);
        $this->dealer = app(DealerService::class)->register([
            'business_name' => 'Billing Tractors',
            'display_name' => 'Billing Tractors',
            'mobile' => '9800000009',
        ], [], User::factory()->create(['user_type' => 'customer']));
    }

    private function listing(): UsedListing
    {
        return UsedListing::create([
            'reference_no' => 'KJ-U-TEST1',
            'user_id' => User::factory()->create(['user_type' => 'customer'])->id,
            'title' => 'Test listing',
            'slug' => 'test-listing',
            'manufacturing_year' => 2019,
            'expected_price' => 450000,
            'status' => 'live',
        ]);
    }

    private function paidPlan(): Plan
    {
        return Plan::where('audience', 'dealer')->where('price', '>', 0)->orderBy('price')->firstOrFail();
    }

    public function test_checkout_opens_an_order_but_does_not_start_the_plan(): void
    {
        $result = $this->billing->subscribe($this->dealer, $this->paidPlan());

        $this->assertSame('pending_payment', $result['subscription']->status);
        $this->assertSame('created', $result['payment']->status);
        $this->assertNotNull($result['checkout']['order_id']);

        // The free plan the dealer signed up on is still the live one.
        $this->assertSame('Free', $this->dealer->activeSubscription()->with('plan')->first()?->plan?->name);
    }

    public function test_a_verified_callback_starts_the_plan_and_closes_the_old_one(): void
    {
        $plan = $this->paidPlan();
        $result = $this->billing->subscribe($this->dealer, $plan);

        $this->assertTrue($this->billing->settle($result['payment'], ['payment_id' => 'pay_test_1']));

        $active = $this->dealer->refresh()->activeSubscription()->with('plan')->first();

        $this->assertSame($plan->name, $active?->plan?->name);
        $this->assertSame(1, $this->dealer->subscriptions()->where('status', 'active')->count());
    }

    public function test_a_failed_signature_grants_nothing(): void
    {
        $this->app->bind(PaymentGateway::class, fn () => new class implements PaymentGateway
        {
            public function createOrder(Payment $payment): array
            {
                return ['order_id' => 'order_broken'];
            }

            public function verify(Payment $payment, array $callback): bool
            {
                return false;
            }
        });

        $billing = app(BillingService::class);
        $result = $billing->subscribe($this->dealer, $this->paidPlan());

        $this->assertFalse($billing->settle($result['payment'], ['payment_id' => 'forged']));
        $this->assertSame('failed', $result['payment']->refresh()->status);
        $this->assertSame('pending_payment', $result['subscription']->refresh()->status);
        $this->assertSame('Free', $this->dealer->refresh()->activeSubscription()->with('plan')->first()?->plan?->name);
    }

    public function test_a_payment_is_never_granted_twice(): void
    {
        $result = $this->billing->subscribe($this->dealer, $this->paidPlan());

        $this->billing->settle($result['payment'], ['payment_id' => 'pay_test_2']);
        $this->billing->settle($result['payment']->refresh(), ['payment_id' => 'pay_test_2']);

        $this->assertSame(1, $this->dealer->refresh()->subscriptions()->where('status', 'active')->count());
    }

    public function test_a_paid_boost_promotes_the_listing_only_once_it_is_paid(): void
    {
        $listing = $this->listing();
        $package = Plan::where('audience', 'seller')->orderBy('price')->firstOrFail();

        $result = $this->billing->boost($listing, $package);

        $this->assertFalse((bool) $listing->refresh()->is_featured);

        $this->billing->settle($result['payment'], ['payment_id' => 'pay_boost_1']);

        $this->assertTrue((bool) $listing->refresh()->is_featured);
        $this->assertSame('active', $result['boost']->refresh()->status);
    }

    public function test_a_promotion_ends_when_the_paid_window_does(): void
    {
        $listing = $this->listing();
        $result = $this->billing->boost($listing, Plan::where('audience', 'seller')->orderBy('price')->firstOrFail());
        $this->billing->settle($result['payment'], ['payment_id' => 'pay_boost_2']);

        $this->travel(40)->days();
        $this->artisan('boosts:expire')->assertSuccessful();

        $this->assertFalse((bool) $listing->refresh()->is_featured);
        $this->assertSame('expired', $result['boost']->refresh()->status);
    }

    public function test_a_free_plan_needs_no_gateway_at_all(): void
    {
        $free = Plan::where('slug', 'free')->firstOrFail();

        $result = $this->billing->subscribe($this->dealer, $free);

        $this->assertNull($result['payment']);
        $this->assertSame('active', $result['subscription']->status);
    }
}
