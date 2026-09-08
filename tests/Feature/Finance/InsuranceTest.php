<?php

namespace Tests\Feature\Finance;

use App\Domain\Finance\Services\InsuranceService;
use App\Models\InsuranceEnquiry;
use App\Models\InsurancePartner;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\FinanceMasterSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceTest extends TestCase
{
    use RefreshDatabase;

    private InsuranceService $insurance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, MarketplaceMasterSeeder::class, FinanceMasterSeeder::class,
        ]);
        $this->insurance = app(InsuranceService::class);
    }

    public function test_the_page_renders_the_partner_panel_without_javascript(): void
    {
        $this->get(route('insurance.index'))
            ->assertOk()
            ->assertSee('New India Assurance')
            ->assertSee('Third party only');
    }

    public function test_an_enquiry_opens_a_routable_lead_as_well_as_its_own_record(): void
    {
        $user = User::factory()->create(['user_type' => 'customer']);

        $enquiry = $this->insurance->capture([
            'name' => $user->name,
            'mobile' => $user->mobile,
            'coverage_type' => 'comprehensive',
            'registration_number' => 'UP34 AB 4512',
        ], $user);

        $lead = Lead::where('type', 'insurance')->firstOrFail();

        $this->assertSame(1, InsuranceEnquiry::count());
        $this->assertStringStartsWith('KJ-N-', $enquiry->reference_no);
        $this->assertSame($enquiry->reference_no, $lead->meta['insurance_reference']);
        $this->assertSame('new', $enquiry->status);
    }

    public function test_a_partner_serving_named_states_is_hidden_elsewhere(): void
    {
        $partner = InsurancePartner::first();
        $partner->forceFill(['states_served' => [99]])->save();

        $nationwide = collect($this->insurance->partnersFor(null))->pluck('id');
        $elsewhere = collect($this->insurance->partnersFor(1))->pluck('id');

        $this->assertTrue($nationwide->contains($partner->id));
        $this->assertFalse($elsewhere->contains($partner->id));
    }

    public function test_the_partner_list_survives_a_cache_round_trip(): void
    {
        $partners = $this->insurance->partnersFor(null);

        // Plain arrays only: a serialised Eloquent collection comes back broken.
        $this->assertIsArray($partners);
        $this->assertEquals($partners, unserialize(serialize($partners)));
    }
}
