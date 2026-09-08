<?php

namespace Tests\Feature\Finance;

use App\Domain\Finance\Services\LoanApplicationService;
use App\Models\LoanApplication;
use App\Models\User;
use App\Support\DocumentVault;
use Database\Seeders\FinanceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * KYC and loan documents are the most sensitive thing the product holds.
 *
 * These tests exist to make sure the three guards stay in place together: the
 * file is off the public disk, the link expires, and the signature alone is
 * never enough — who is asking is still checked.
 */
class DocumentPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private LoanApplication $application;

    private User $applicant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, NotificationTemplateSeeder::class, FinanceMasterSeeder::class]);

        Storage::fake('private');
        Storage::fake('public');

        $this->applicant = User::factory()->create(['user_type' => 'customer']);

        $loans = app(LoanApplicationService::class);

        $this->application = $loans->saveDraft([
            'applicant_name' => $this->applicant->name,
            'mobile' => $this->applicant->mobile,
            'machinery_price' => 600000,
            'down_payment' => 100000,
            'tenure_months' => 48,
        ], null, $this->applicant->id);

        $loans->attachDocument($this->application, UploadedFile::fake()->create('aadhaar.pdf', 40), 'aadhaar');
    }

    private function document()
    {
        return $this->application->documents()->firstOrFail();
    }

    public function test_the_file_lands_on_the_private_disk_and_not_the_public_one(): void
    {
        $path = $this->document()->file_path;

        Storage::disk('private')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_the_owner_can_download_through_a_signed_link(): void
    {
        $url = app(DocumentVault::class)->temporaryUrl('documents.loan', ['document' => $this->document()]);

        $this->actingAs($this->applicant)->get($url)->assertOk();
    }

    public function test_an_unsigned_url_is_refused_even_for_the_owner(): void
    {
        $this->actingAs($this->applicant)
            ->get(route('documents.loan', $this->document()))
            ->assertForbidden();
    }

    public function test_an_expired_link_is_refused(): void
    {
        $url = app(DocumentVault::class)->temporaryUrl('documents.loan', ['document' => $this->document()]);

        $this->travel(DocumentVault::LINK_TTL_MINUTES + 1)->minutes();

        $this->actingAs($this->applicant)->get($url)->assertForbidden();
    }

    public function test_a_valid_signature_does_not_let_another_customer_in(): void
    {
        $stranger = User::factory()->create(['user_type' => 'customer']);
        $url = app(DocumentVault::class)->temporaryUrl('documents.loan', ['document' => $this->document()]);

        $this->actingAs($stranger)->get($url)->assertForbidden();
    }

    public function test_the_finance_desk_can_open_it(): void
    {
        $officer = User::factory()->create(['user_type' => 'staff']);
        $officer->assignRole('finance-executive');

        $url = app(DocumentVault::class)->temporaryUrl('documents.loan', ['document' => $this->document()]);

        $this->actingAs($officer)->get($url)->assertOk();
    }

    public function test_a_guest_is_sent_to_login_rather_than_served_the_file(): void
    {
        $url = app(DocumentVault::class)->temporaryUrl('documents.loan', ['document' => $this->document()]);

        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_identifiers_are_masked_to_the_last_four_characters(): void
    {
        $this->assertSame('XXXXXX234F', DocumentVault::mask('ABCDE1234F'));
        $this->assertSame('XXXXXXXX1012', DocumentVault::mask('987654321012'));
        $this->assertNull(DocumentVault::mask(null));
    }
}
