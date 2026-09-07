<?php

namespace Tests\Feature\Leads;

use App\Domain\Lead\Services\LeadService;
use App\Domain\Marketplace\Services\ListingService;
use App\Models\Dealer;
use App\Models\District;
use App\Models\Lead;
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

class LeadEngineTest extends TestCase
{
    use RefreshDatabase;

    private LeadService $leads;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, NotificationTemplateSeeder::class,
            CatalogMasterSeeder::class, DemoProductSeeder::class, MarketplaceMasterSeeder::class,
        ]);
        $this->leads = app(LeadService::class);

        // Production always has staff; without one there is genuinely nobody to
        // route to, which is a separate case tested below.
        $this->staff = User::factory()->create(['user_type' => 'staff']);
        $this->staff->assignRole('sales-executive');
    }

    private User $staff;

    private function capture(array $overrides = [], ?Product $product = null): Lead
    {
        return $this->leads->capture('new_product', [
            'name' => 'Ramesh Kumar',
            'mobile' => '9876500001',
            'mobile_verified' => true,
            ...$overrides,
        ], $product ?? Product::with('brand')->firstOrFail());
    }

    public function test_capturing_a_lead_assigns_a_reference_and_routes_it(): void
    {
        $lead = $this->capture();

        $this->assertStringStartsWith('KJ-L-', $lead->reference_no);
        $this->assertSame('assigned', $lead->status);
        $this->assertDatabaseHas('lead_assignments', ['lead_id' => $lead->id]);
    }

    public function test_a_lead_with_nobody_to_route_to_stays_visible_and_is_flagged(): void
    {
        // No dealers, no staff — the lead must not vanish silently.
        $this->staff->delete();
        User::staff()->delete();

        $lead = $this->capture();

        $this->assertSame('new', $lead->status);
        $this->assertDatabaseCount('lead_assignments', 0);
        $this->assertTrue($lead->activities()->where('description', 'like', '%manual routing%')->exists());
    }

    public function test_the_same_person_asking_twice_is_one_lead(): void
    {
        $product = Product::with('brand')->firstOrFail();

        $first = $this->capture([], $product);
        $second = $this->capture([], $product);

        $this->assertSame('duplicate', $second->status);
        $this->assertSame($first->id, $second->duplicate_of_id);

        // The original picks up a note rather than the dealer getting two leads.
        $this->assertTrue($first->activities()->where('activity', 'note')->exists());
    }

    public function test_a_different_person_is_not_a_duplicate(): void
    {
        $product = Product::with('brand')->firstOrFail();

        $this->capture([], $product);
        $other = $this->capture(['mobile' => '9876500002'], $product);

        $this->assertNotSame('duplicate', $other->status);
    }

    public function test_an_enquiry_about_a_listing_goes_to_its_seller(): void
    {
        $seller = User::factory()->create();
        $listing = $this->liveListing($seller);

        $lead = $this->leads->capture('used_listing', [
            'name' => 'Vijay', 'mobile' => '9876500003', 'mobile_verified' => true,
        ], $listing);

        $assignment = $lead->fresh()->currentAssignment;

        $this->assertSame('seller', $assignment->assignee_type);
        $this->assertSame($seller->id, $assignment->user_id);
    }

    public function test_a_lead_prefers_a_dealer_in_the_same_district(): void
    {
        $product = Product::with('brand')->firstOrFail();
        $district = District::firstOrFail();

        $local = $this->dealer($district->id, $product->brand_id);
        $this->dealer(District::where('id', '!=', $district->id)->first()->id, $product->brand_id);

        $lead = $this->capture(['state_id' => $district->state_id, 'district_id' => $district->id], $product);

        $this->assertSame($local->id, $lead->fresh()->currentAssignment->dealer_id);
    }

    public function test_routing_records_which_rule_decided(): void
    {
        $lead = $this->capture();

        $this->assertNotNull($lead->fresh()->currentAssignment->routing_rule_id,
            'An assignment should say which rule produced it.');
    }

    public function test_an_illegal_status_change_is_refused(): void
    {
        $lead = $this->capture();

        $this->expectException(\InvalidArgumentException::class);
        $this->leads->changeStatus($lead, 'converted'); // must be contacted first
    }

    public function test_progressing_a_lead_records_the_change(): void
    {
        $lead = $this->capture();

        $this->leads->changeStatus($lead, 'contacted');
        $this->leads->changeStatus($lead->fresh(), 'qualified');
        $lead = $this->leads->changeStatus($lead->fresh(), 'converted');

        $this->assertSame('converted', $lead->status);
        $this->assertNotNull($lead->first_contacted_at);
        $this->assertNotNull($lead->converted_at);
        $this->assertSame(3, $lead->activities()->where('activity', 'status_change')->count());
    }

    public function test_a_prompt_dealer_response_improves_their_score(): void
    {
        $product = Product::with('brand')->firstOrFail();
        $district = District::firstOrFail();
        $dealer = $this->dealer($district->id, $product->brand_id);
        $before = (float) $dealer->response_score;

        $lead = $this->capture(['state_id' => $district->state_id, 'district_id' => $district->id], $product);
        $this->leads->changeStatus($lead->fresh(), 'contacted');

        $this->assertGreaterThan($before, (float) $dealer->fresh()->response_score);
    }

    public function test_an_unanswered_lead_is_escalated_and_costs_the_dealer(): void
    {
        $product = Product::with('brand')->firstOrFail();
        $district = District::firstOrFail();
        $dealer = $this->dealer($district->id, $product->brand_id);
        $before = (float) $dealer->response_score;

        $lead = $this->capture(['state_id' => $district->state_id, 'district_id' => $district->id], $product);

        // Push the assignment past the SLA.
        $lead->fresh()->currentAssignment->forceFill([
            'assigned_at' => now()->subMinutes(config('kj.leads.response_sla_minutes') + 5),
        ])->save();

        $this->artisan('leads:escalate')->assertSuccessful();

        $this->assertLessThan($before, (float) $dealer->fresh()->response_score);
        $this->assertTrue($lead->activities()->where('description', 'like', '%SLA%')->exists());
    }

    public function test_merging_marks_the_duplicate_and_notes_the_original(): void
    {
        $original = $this->capture();
        $other = $this->capture(['mobile' => '9876500009'], Product::with('brand')->skip(1)->first());

        $this->leads->merge($other, $original);

        $this->assertSame('duplicate', $other->fresh()->status);
        $this->assertSame($original->id, $other->fresh()->duplicate_of_id);
    }

    private function dealer(int $districtId, int $brandId): Dealer
    {
        $district = District::findOrFail($districtId);
        $owner = User::factory()->create(['user_type' => 'dealer']);

        $dealer = Dealer::create([
            'code' => 'KJ-D-'.str_pad((string) (Dealer::count() + 1), 5, '0', STR_PAD_LEFT),
            'owner_user_id' => $owner->id,
            'business_name' => 'Test Motors '.$districtId,
            'display_name' => 'Test Motors '.$districtId,
            'slug' => 'test-motors-'.$districtId.'-'.uniqid(),
            'mobile' => (string) fake()->numberBetween(6000000000, 9999999999),
            'state_id' => $district->state_id,
            'district_id' => $district->id,
            'verification_status' => 'verified',
            'is_active' => true,
        ]);

        $dealer->brands()->attach($brandId);

        return $dealer;
    }

    private function liveListing(User $seller): UsedListing
    {
        $listings = app(ListingService::class);
        $product = Product::with('brand')->firstOrFail();
        $district = District::firstOrFail();

        $draft = $listings->saveDraft([
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'product_id' => $product->id,
            'manufacturing_year' => 2019,
            'condition' => 'good',
            'expected_price' => 350000,
            'state_id' => $district->state_id,
            'district_id' => $district->id,
        ], null, $seller->id);

        return $listings->approve($listings->submit($draft));
    }
}
