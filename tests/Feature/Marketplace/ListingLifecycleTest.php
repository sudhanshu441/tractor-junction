<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Marketplace\Services\ListingService;
use App\Models\District;
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
use Tests\TestCase;

class ListingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private ListingService $listings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, NotificationTemplateSeeder::class,
            CatalogMasterSeeder::class, DemoProductSeeder::class, MarketplaceMasterSeeder::class,
        ]);
        $this->listings = app(ListingService::class);
    }

    private function draft(array $overrides = []): UsedListing
    {
        $seller = User::factory()->create();
        $product = Product::with('brand')->firstOrFail();
        $district = District::firstOrFail();

        return $this->listings->saveDraft([
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'product_id' => $product->id,
            'manufacturing_year' => 2019,
            'engine_hours' => 2100,
            'condition' => 'good',
            'expected_price' => 385000,
            'state_id' => $district->state_id,
            'district_id' => $district->id,
            ...$overrides,
        ], null, $seller->id);
    }

    public function test_a_draft_starts_without_a_title_or_slug(): void
    {
        $draft = $this->draft();

        $this->assertSame('draft', $draft->status);
        $this->assertNull($draft->title);
        $this->assertStringStartsWith('KJ-U-', $draft->reference_no);
    }

    public function test_submitting_titles_and_slugs_the_listing(): void
    {
        $listing = $this->listings->submit($this->draft());

        $this->assertSame('pending', $listing->status);
        $this->assertNotNull($listing->title);
        $this->assertNotNull($listing->slug);
        $this->assertDatabaseHas('used_listing_status_logs', [
            'used_listing_id' => $listing->id, 'from_status' => 'draft', 'to_status' => 'pending',
        ]);
    }

    public function test_an_incomplete_draft_cannot_be_submitted(): void
    {
        $draft = $this->draft(['expected_price' => null]);

        $this->expectException(\InvalidArgumentException::class);
        $this->listings->submit($draft);
    }

    public function test_approving_publishes_and_sets_an_expiry(): void
    {
        $listing = $this->listings->approve($this->listings->submit($this->draft()));

        $this->assertSame('live', $listing->status);
        $this->assertNotNull($listing->published_at);
        $this->assertTrue($listing->expires_at->isAfter(now()->addDays(config('kj.listings.expiry_days') - 1)));
    }

    public function test_rejecting_keeps_the_reason_for_the_seller(): void
    {
        $listing = $this->listings->reject($this->listings->submit($this->draft()), 'Photos are unclear');

        $this->assertSame('rejected', $listing->status);
        $this->assertSame('Photos are unclear', $listing->rejection_reason);
    }

    public function test_an_illegal_transition_is_refused(): void
    {
        $draft = $this->draft();

        // A draft has never been reviewed, so it cannot jump straight to live.
        $this->expectException(\InvalidArgumentException::class);
        $this->listings->transition($draft, 'live');
    }

    public function test_a_sold_listing_is_terminal(): void
    {
        $listing = $this->listings->markSold($this->listings->approve($this->listings->submit($this->draft())));

        $this->assertSame('sold', $listing->status);

        $this->expectException(\InvalidArgumentException::class);
        $this->listings->transition($listing, 'live');
    }

    public function test_every_transition_writes_an_audit_row(): void
    {
        $listing = $this->listings->approve($this->listings->submit($this->draft()));
        $this->listings->markSold($listing);

        $this->assertSame(3, $listing->statusLogs()->count()); // pending, live, sold
    }

    public function test_the_expiry_command_expires_only_overdue_listings(): void
    {
        $overdue = $this->listings->approve($this->listings->submit($this->draft()));
        $overdue->forceFill(['expires_at' => now()->subDay()])->save();

        $fresh = $this->listings->approve($this->listings->submit($this->draft()));

        $this->artisan('listings:expire')->assertSuccessful();

        $this->assertSame('expired', $overdue->fresh()->status);
        $this->assertSame('live', $fresh->fresh()->status);
    }

    public function test_renewing_an_expired_listing_puts_it_back_on_sale(): void
    {
        $listing = $this->listings->approve($this->listings->submit($this->draft()));
        $this->listings->expire($listing);

        $renewed = $this->listings->renew($listing->fresh());

        $this->assertSame('live', $renewed->status);
        $this->assertTrue($renewed->expires_at->isFuture());
    }

    public function test_only_live_listings_are_publicly_visible(): void
    {
        $pending = $this->listings->submit($this->draft());
        $pending->forceFill(['slug' => 'a-pending-listing'])->save();

        $this->get('/used/listing/a-pending-listing')->assertNotFound();

        $live = $this->listings->approve($this->listings->submit($this->draft()));

        $this->get('/used/listing/'.$live->slug)->assertOk()->assertSee($live->title);
    }
}
