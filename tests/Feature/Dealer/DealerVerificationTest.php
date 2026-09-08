<?php

namespace Tests\Feature\Dealer;

use App\Domain\Dealer\Services\DealerService;
use App\Models\Dealer;
use App\Models\User;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DealerVerificationTest extends TestCase
{
    use RefreshDatabase;

    private DealerService $dealers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class,
            NotificationTemplateSeeder::class, MarketplaceMasterSeeder::class,
        ]);
        $this->dealers = app(DealerService::class);
    }

    private function register(?User $owner = null): Dealer
    {
        return $this->dealers->register([
            'business_name' => 'Test Tractors',
            'display_name' => 'Test Tractors',
            'mobile' => '9800000001',
        ], [], $owner ?? User::factory()->create(['user_type' => 'customer']));
    }

    public function test_a_new_dealer_starts_pending_and_never_verified(): void
    {
        $this->assertSame('pending', $this->register()->verification_status);
    }

    public function test_registering_promotes_the_owner_into_the_dealer_panel(): void
    {
        $owner = User::factory()->create(['user_type' => 'customer']);

        $this->register($owner);

        $owner->refresh();

        $this->assertSame('dealer', $owner->user_type);
        $this->assertTrue($owner->hasRole('dealer-owner'));
    }

    public function test_a_new_dealer_is_put_on_the_free_plan(): void
    {
        $dealer = $this->register();

        $this->assertSame('Free', $dealer->activeSubscription()->with('plan')->first()?->plan?->name);
    }

    public function test_a_pending_dealer_cannot_be_suspended_before_being_verified(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->dealers->changeStatus($this->register(), 'suspended');
    }

    public function test_verification_records_who_did_it_and_when(): void
    {
        $approver = User::factory()->create(['user_type' => 'staff']);
        $approver->assignRole('admin');

        $this->actingAs($approver);

        $dealer = $this->dealers->changeStatus($this->register(), 'verified', 'Papers checked');

        $this->assertSame('verified', $dealer->verification_status);
        $this->assertSame($approver->id, $dealer->verified_by);
        $this->assertNotNull($dealer->verified_at);
    }

    public function test_a_pending_dealer_is_kept_out_of_the_public_directory(): void
    {
        $this->register();

        $this->get(route('dealers.index'))->assertOk()->assertDontSee('Test Tractors');
    }

    public function test_a_verified_dealer_appears_in_the_public_directory(): void
    {
        $dealer = $this->dealers->changeStatus($this->register(), 'verified');

        $this->get(route('dealers.index'))->assertOk()->assertSee('Test Tractors');
        $this->get(route('dealers.show', $dealer->slug))->assertOk();
    }

    public function test_a_dealers_lead_and_inventory_allowance_comes_from_the_plan(): void
    {
        $usage = $this->dealers->planUsage($this->register());

        $this->assertSame('Free', $usage['plan']);
        $this->assertSame(0, $usage['used']);
        $this->assertSame(2, $usage['daily_cap']);
    }
}
