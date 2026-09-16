<?php

namespace Tests\Feature\Reporting;

use App\Domain\Reporting\Services\ReportCatalogue;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DemoDealerSeeder;
use Database\Seeders\DemoFinanceSeeder;
use Database\Seeders\DemoListingSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\FinanceMasterSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, CatalogMasterSeeder::class, DemoProductSeeder::class,
            MarketplaceMasterSeeder::class, FinanceMasterSeeder::class, DemoListingSeeder::class,
            DemoDealerSeeder::class, DemoFinanceSeeder::class, ContentSeeder::class,
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['user_type' => 'staff']);
        $user->assignRole('super-admin');

        return $user;
    }

    public static function reports(): array
    {
        return array_map(fn ($key) => [$key], array_keys(ReportCatalogue::index()));
    }

    #[DataProvider('reports')]
    public function test_every_report_downloads_as_a_spreadsheet(string $report): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.download', ['report' => $report, 'format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('content-type',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // A real xlsx is a zip; an error page is not.
        $this->assertStringStartsWith('PK', $this->body($response));
    }

    #[DataProvider('reports')]
    public function test_every_report_downloads_as_a_pdf(string $report): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('admin.reports.download', ['report' => $report, 'format' => 'pdf']));

        $response->assertOk();
        $this->assertStringStartsWith('%PDF-', $this->body($response));
    }

    public function test_a_csv_carries_a_byte_order_mark_so_excel_reads_hindi_correctly(): void
    {
        $body = $this->body($this->actingAs($this->admin())
            ->get(route('admin.reports.download', ['report' => 'tractors', 'format' => 'csv'])));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
    }

    public function test_a_report_contains_the_rows_it_says_it_does(): void
    {
        $body = $this->body($this->actingAs($this->admin())
            ->get(route('admin.reports.download', ['report' => 'tractors', 'format' => 'csv'])));

        $rows = array_filter(explode("\n", trim($body)));

        // Header plus every active product.
        $this->assertCount(Product::count() + 1, $rows);
        $this->assertStringContainsString('Mahindra', $body);
    }

    public function test_an_unknown_report_or_format_is_a_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.reports.download', ['report' => 'salaries', 'format' => 'csv']))
            ->assertNotFound();

        $this->actingAs($admin)->get('/admin/reports/leads/docx')->assertNotFound();
    }

    public function test_a_role_without_the_reports_permission_cannot_download(): void
    {
        $editor = User::factory()->create(['user_type' => 'staff']);
        $editor->assignRole('content-editor');

        $this->actingAs($editor)
            ->get(route('admin.reports.download', ['report' => 'loans', 'format' => 'xlsx']))
            ->assertForbidden();
    }

    /**
     * The whole point of masking on screen is undone if the export hands over
     * the real numbers.
     */
    public function test_an_export_masks_contact_numbers_for_a_role_that_sees_them_masked(): void
    {
        $editor = User::factory()->create(['user_type' => 'staff']);
        $editor->assignRole('content-editor');
        $editor->givePermissionTo('reports.view', 'users.export');

        $this->assertFalse($editor->can('leads.view_contact'));

        $body = $this->body($this->actingAs($editor)
            ->get(route('admin.reports.download', ['report' => 'customers', 'format' => 'csv'])));

        $seller = User::where('user_type', 'customer')->firstOrFail();

        $this->assertStringNotContainsString($seller->mobile, $body);
        $this->assertStringContainsString($seller->masked_mobile, $body);
    }

    public function test_the_same_export_shows_full_numbers_to_a_role_that_is_allowed_them(): void
    {
        $body = $this->body($this->actingAs($this->admin())
            ->get(route('admin.reports.download', ['report' => 'customers', 'format' => 'csv'])));

        $seller = User::where('user_type', 'customer')->firstOrFail();

        $this->assertStringContainsString($seller->mobile, $body);
    }

    public function test_a_download_is_recorded_in_the_activity_log(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.download', ['report' => 'dealers', 'format' => 'xlsx']))
            ->assertOk();

        $this->assertDatabaseHas('activity_log', ['description' => 'Downloaded a report']);
    }

    private function body($response): string
    {
        ob_start();
        $response->sendContent();

        return ob_get_clean();
    }
}
