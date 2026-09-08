<?php

namespace Tests\Feature\Finance;

use App\Domain\Finance\Services\LoanApplicationService;
use App\Models\Country;
use App\Models\Lender;
use App\Models\LoanApplication;
use App\Models\LoanStatusLog;
use App\Models\State;
use App\Models\User;
use Database\Seeders\FinanceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanApplicationTest extends TestCase
{
    use RefreshDatabase;

    private LoanApplicationService $loans;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, NotificationTemplateSeeder::class, FinanceMasterSeeder::class]);
        $this->loans = app(LoanApplicationService::class);
    }

    private function draft(array $overrides = []): LoanApplication
    {
        $applicant = User::factory()->create(['user_type' => 'customer']);

        return $this->loans->saveDraft([
            'applicant_name' => $applicant->name,
            'mobile' => $applicant->mobile,
            'machinery_price' => 700000,
            'down_payment' => 150000,
            'tenure_months' => 60,
            'expected_interest_rate' => 12.0,
            'annual_income' => 400000,
            ...$overrides,
        ], null, $applicant->id);
    }

    public function test_the_server_computes_the_emi_and_ignores_whatever_the_browser_sent(): void
    {
        $application = $this->draft(['calculated_emi' => 1, 'loan_amount' => 1]);

        $this->assertSame('550000.00', $application->loan_amount);
        $this->assertEqualsWithDelta(12234.44, (float) $application->calculated_emi, 1.0);
    }

    public function test_kyc_identifiers_are_stored_masked_and_never_in_full(): void
    {
        $data = $this->loans->maskIdentifiers(['pan' => 'ABCDE1234F', 'aadhaar' => '987654321012']);

        $this->assertArrayNotHasKey('pan', $data);
        $this->assertArrayNotHasKey('aadhaar', $data);
        $this->assertSame('XXXXXX234F', $data['pan_masked']);
        $this->assertStringEndsWith('1012', $data['aadhaar_masked']);
        $this->assertStringNotContainsString('98765', $data['aadhaar_masked']);
    }

    public function test_every_status_change_writes_an_audit_row(): void
    {
        $application = $this->loans->submit($this->draft());

        $this->loans->changeStatus($application->refresh(), 'under_review', 'Picked up');
        $this->loans->changeStatus($application->refresh(), 'sent_to_lender');
        $this->loans->changeStatus($application->refresh(), 'sanctioned');

        $logs = LoanStatusLog::where('loan_application_id', $application->id)->get();

        $this->assertCount(4, $logs);
        $this->assertSame('sanctioned', $logs->last()->to_status);
    }

    public function test_an_illegal_transition_is_refused(): void
    {
        $application = $this->draft();

        $this->expectException(\InvalidArgumentException::class);

        $this->loans->changeStatus($application, 'disbursed');
    }

    public function test_a_draft_saves_before_the_machine_or_price_is_known(): void
    {
        $application = $this->draft(['machinery_price' => 0]);

        $this->assertSame('draft', $application->status);
        $this->assertSame('0.00', $application->loan_amount);
    }

    public function test_an_application_missing_its_essentials_cannot_be_submitted(): void
    {
        $application = $this->draft();
        $application->forceFill(['mobile' => ''])->save();

        $this->expectException(\InvalidArgumentException::class);

        $this->loans->submit($application->refresh());
    }

    public function test_only_lenders_whose_criteria_the_application_meets_are_offered(): void
    {
        // ₹5.5 lakh over 60 months rules out the co-operative bank (₹8 lakh cap,
        // but a 60-month tenure inside its band) and keeps the national lenders.
        $small = $this->loans->matchingLenders($this->draft());
        $large = $this->loans->matchingLenders($this->draft([
            'machinery_price' => 2800000, 'down_payment' => 200000,
        ]));

        $this->assertGreaterThan($large->count(), $small->count());
        $this->assertTrue($large->every(fn ($lender) => (float) $lender->amount_max >= 2600000));
    }

    public function test_a_lender_that_does_not_operate_in_the_state_is_not_offered(): void
    {
        $country = Country::firstOrCreate(
            ['iso2' => 'IN'],
            ['name' => 'India', 'iso2' => 'IN', 'iso3' => 'IND', 'currency' => 'INR', 'phone_code' => '+91'],
        );

        $state = State::firstOrCreate(
            ['code' => 'ZZ'],
            ['name' => 'Test State', 'slug' => 'test-state', 'country_id' => $country->id, 'is_active' => true],
        );

        Lender::query()->update(['states_served' => ['XX']]);

        $application = $this->draft(['state_id' => $state->id]);

        // The relation has to resolve for this filter to run at all — without it
        // the state check silently passes for every lender.
        $this->assertSame('ZZ', $application->state?->code);
        $this->assertCount(0, $this->loans->matchingLenders($application));
    }

    public function test_documents_still_outstanding_are_reported(): void
    {
        $application = $this->draft();

        $this->assertSame(LoanApplicationService::REQUIRED_DOCUMENTS, $this->loans->missingDocuments($application));
    }
}
