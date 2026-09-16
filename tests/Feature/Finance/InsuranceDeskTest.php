<?php

namespace Tests\Feature\Finance;

use App\Models\InsuranceEnquiry;
use App\Models\InsurancePartner;
use App\Models\User;
use Database\Seeders\FinanceMasterSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsuranceDeskTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, MarketplaceMasterSeeder::class, FinanceMasterSeeder::class,
        ]);
    }

    private function enquiry(array $overrides = []): InsuranceEnquiry
    {
        return InsuranceEnquiry::create([
            'reference_no' => 'KJ-N-'.str_pad((string) random_int(1, 99999), 6, '0', STR_PAD_LEFT),
            'applicant_name' => 'Ramesh Kumar',
            'mobile' => '9812345670',
            'coverage_type' => 'comprehensive',
            'status' => 'new',
            ...$overrides,
        ]);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['user_type' => 'staff']);
        $user->assignRole($role);

        return $user;
    }

    public function test_the_desk_lists_enquiries_with_their_counts(): void
    {
        $this->enquiry();
        $this->enquiry(['status' => 'converted', 'mobile' => '9812345671']);

        $this->actingAs($this->staff('finance-executive'))
            ->get(route('admin.insurance.index'))
            ->assertOk()
            ->assertSee('Ramesh Kumar')
            ->assertSee('comprehensive');
    }

    public function test_an_enquiry_moves_through_its_stages(): void
    {
        $enquiry = $this->enquiry();
        $staff = $this->staff('finance-executive');

        $this->actingAs($staff)
            ->postJson(route('admin.insurance.update', $enquiry), ['status' => 'contacted'])
            ->assertOk();

        $this->assertSame('contacted', $enquiry->fresh()->status);
    }

    public function test_a_stage_cannot_be_skipped(): void
    {
        $enquiry = $this->enquiry();

        $this->actingAs($this->staff('finance-executive'))
            ->postJson(route('admin.insurance.update', $enquiry), ['status' => 'converted'])
            ->assertStatus(422);

        $this->assertSame('new', $enquiry->fresh()->status);
    }

    public function test_an_insurer_and_an_owner_can_be_set(): void
    {
        $enquiry = $this->enquiry();
        $staff = $this->staff('finance-executive');
        $partner = InsurancePartner::firstOrFail();

        $this->actingAs($staff)
            ->postJson(route('admin.insurance.update', $enquiry), [
                'insurance_partner_id' => $partner->id,
                'assigned_to' => $staff->id,
            ])
            ->assertOk();

        $enquiry->refresh();

        $this->assertSame($partner->id, $enquiry->insurance_partner_id);
        $this->assertSame($staff->id, $enquiry->assigned_to);
    }

    public function test_a_role_without_insurance_access_is_refused(): void
    {
        $editor = $this->staff('content-editor');

        $this->actingAs($editor)->get(route('admin.insurance.index'))->assertForbidden();
        $this->actingAs($editor)
            ->postJson(route('admin.insurance.update', $this->enquiry()), ['status' => 'contacted'])
            ->assertForbidden();
    }

    public function test_contact_numbers_are_masked_for_a_role_that_may_not_see_them(): void
    {
        $enquiry = $this->enquiry();
        $inspector = $this->staff('inspector');
        $inspector->givePermissionTo('insurance.view');

        $this->assertFalse($inspector->can('leads.view_contact'));

        $this->actingAs($inspector)->get(route('admin.insurance.index'))
            ->assertOk()
            ->assertDontSee($enquiry->mobile)
            ->assertSee($enquiry->masked_mobile);
    }

    public function test_a_policy_expiring_soon_is_flagged_as_the_call_to_make_first(): void
    {
        $this->enquiry(['previous_policy_expiry' => today()->addDays(10)]);

        $this->actingAs($this->staff('finance-executive'))
            ->get(route('admin.insurance.index'))
            ->assertOk()
            ->assertSee('expiring within 30 days', false);
    }
}
