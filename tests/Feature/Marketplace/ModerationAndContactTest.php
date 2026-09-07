<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Marketplace\Services\ListingService;
use App\Models\District;
use App\Models\OtpVerification;
use App\Models\Product;
use App\Models\UsedListing;
use App\Models\User;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ModerationAndContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, NotificationTemplateSeeder::class,
            CatalogMasterSeeder::class, DemoProductSeeder::class, MarketplaceMasterSeeder::class,
        ]);
    }

    private function moderator(): User
    {
        $user = User::factory()->create(['user_type' => 'staff']);
        $user->assignRole('moderator');

        return $user;
    }

    private function pendingListing(?User $seller = null): UsedListing
    {
        $listings = app(ListingService::class);
        $product = Product::with('brand')->firstOrFail();
        $district = District::firstOrFail();

        $draft = $listings->saveDraft([
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'product_id' => $product->id,
            'manufacturing_year' => 2019,
            'engine_hours' => 2100,
            'condition' => 'good',
            'expected_price' => 385000,
            'state_id' => $district->state_id,
            'district_id' => $district->id,
        ], null, ($seller ?? User::factory()->create())->id);

        return $listings->submit($draft);
    }

    public function test_the_queue_needs_the_listings_permission(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get('/admin/used-listings/moderation')->assertForbidden();
    }

    public function test_a_moderator_sees_the_next_pending_listing(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs($this->moderator())
            ->get('/admin/used-listings/moderation')
            ->assertOk()
            ->assertSee($listing->reference_no);
    }

    public function test_approving_from_the_queue_publishes_the_listing(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs($this->moderator())
            ->postJson("/admin/used-listings/{$listing->id}/decide", ['decision' => 'approve'])
            ->assertOk()->assertJsonPath('status', 'ok');

        $this->assertSame('live', $listing->fresh()->status);
    }

    public function test_rejecting_requires_a_reason_the_seller_will_read(): void
    {
        $listing = $this->pendingListing();

        $this->actingAs($this->moderator())
            ->postJson("/admin/used-listings/{$listing->id}/decide", ['decision' => 'reject'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');

        $this->assertSame('pending', $listing->fresh()->status);
    }

    public function test_a_rejection_reason_reaches_the_seller(): void
    {
        $seller = User::factory()->create();
        $listing = $this->pendingListing($seller);

        $this->actingAs($this->moderator())
            ->postJson("/admin/used-listings/{$listing->id}/decide", [
                'decision' => 'reject', 'reason' => 'Photos do not show the machine',
            ])->assertOk();

        $this->assertSame('Photos do not show the machine', $listing->fresh()->rejection_reason);
        $this->assertDatabaseHas('notification_logs', ['user_id' => $seller->id, 'channel' => 'sms']);
    }

    public function test_bulk_approve_only_touches_pending_listings(): void
    {
        $pending = $this->pendingListing();
        $live = app(ListingService::class)->approve($this->pendingListing());

        $this->actingAs($this->moderator())
            ->post('/admin/used-listings/bulk-approve', ['listing_ids' => [$pending->id, $live->id]])
            ->assertRedirect();

        $this->assertSame('live', $pending->fresh()->status);
        $this->assertSame(1, $live->fresh()->statusLogs()->where('to_status', 'live')->count());
    }

    public function test_a_buyer_must_verify_before_seeing_the_sellers_number(): void
    {
        $seller = User::factory()->create(['mobile' => '9812311111']);
        $listing = app(ListingService::class)->approve($this->pendingListing($seller));

        // Wrong code: no number, no lead.
        $this->postJson("/ajax/used/{$listing->id}/reveal", [
            'name' => 'Vijay', 'mobile' => '9876500002', 'otp' => '000000',
        ])->assertStatus(422);

        $this->assertDatabaseCount('leads', 0);

        OtpVerification::create([
            'mobile' => '9876500002', 'otp_hash' => Hash::make('123456'),
            'purpose' => 'lead', 'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson("/ajax/used/{$listing->id}/reveal", [
            'name' => 'Vijay', 'mobile' => '9876500002', 'otp' => '123456',
        ])->assertOk()->assertJsonPath('data.mobile', '9812311111');

        // The reveal is a lead the seller can see.
        $this->assertDatabaseHas('leads', ['mobile' => '9876500002', 'type' => 'used_listing']);
        $this->assertSame(1, $listing->fresh()->lead_count);
    }

    public function test_contact_cannot_be_revealed_for_a_listing_that_is_not_live(): void
    {
        $listing = $this->pendingListing();

        $this->postJson("/ajax/used/{$listing->id}/reveal", [
            'name' => 'Vijay', 'mobile' => '9876500002', 'otp' => '123456',
        ])->assertNotFound();
    }

    public function test_a_buyer_can_report_a_listing_once(): void
    {
        $listing = app(ListingService::class)->approve($this->pendingListing());
        $reporter = User::factory()->create();

        $this->actingAs($reporter)
            ->postJson("/ajax/used/{$listing->id}/report", ['reason' => 'sold'])
            ->assertOk();

        $this->actingAs($reporter)
            ->postJson("/ajax/used/{$listing->id}/report", ['reason' => 'fake'])
            ->assertOk();

        $this->assertDatabaseCount('listing_reports', 1);
    }

    public function test_contact_numbers_are_masked_without_the_permission(): void
    {
        $seller = User::factory()->create(['mobile' => '9812311111']);
        $this->pendingListing($seller);

        $moderator = $this->moderator();
        $this->assertFalse($moderator->can('leads.view_contact'));

        $response = $this->actingAs($moderator)
            ->postJson('/admin/used-listings/data', ['draw' => 1, 'start' => 0, 'length' => 10]);

        $response->assertOk();
        $this->assertStringContainsString('98XXXXXX11', json_encode($response->json('data')));
        $this->assertStringNotContainsString('9812311111', json_encode($response->json('data')));
    }
}
