<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Marketplace\Services\InspectionService;
use App\Models\InspectionChecklistItem;
use App\Models\UsedListing;
use App\Models\User;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoListingSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionTest extends TestCase
{
    use RefreshDatabase;

    private InspectionService $inspections;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, CatalogMasterSeeder::class, DemoProductSeeder::class,
            MarketplaceMasterSeeder::class, DemoListingSeeder::class,
        ]);
        $this->inspections = app(InspectionService::class);
    }

    /** A different listing each time: one listing only ever has one inspection. */
    private function listing(int $offset = 0): UsedListing
    {
        return UsedListing::where('status', 'live')->orderBy('id')->skip($offset)->firstOrFail();
    }

    /** @return array<int, array{score: int}> */
    private function scores(int $score): array
    {
        return InspectionChecklistItem::where('is_active', true)->get()
            ->mapWithKeys(fn ($item) => [$item->id => ['score' => $score]])->all();
    }

    private function completed(int $score, int $listingOffset = 0)
    {
        $inspector = User::factory()->create(['user_type' => 'staff']);
        $inspector->assignRole('inspector');

        $inspection = $this->inspections->request($this->listing($listingOffset));
        $this->inspections->schedule($inspection, $inspector->id, now()->addDay()->toDateTimeString());
        $this->inspections->changeStatus($inspection->refresh(), 'in_progress');

        return $this->inspections->complete($inspection->refresh(), $this->scores($score));
    }

    public function test_full_marks_score_a_hundred_and_grade_a(): void
    {
        $inspection = $this->completed(10);

        $this->assertSame('100.00', $inspection->overall_score);
        $this->assertSame('A', $inspection->grade);
    }

    public function test_a_weak_machine_grades_down(): void
    {
        $this->assertSame('D', $this->completed(4)->grade);
        $this->assertSame('C', $this->completed(6, 1)->grade);
    }

    public function test_a_score_outside_the_scale_is_clamped_rather_than_trusted(): void
    {
        $inspector = User::factory()->create(['user_type' => 'staff']);
        $inspection = $this->inspections->request($this->listing());
        $this->inspections->schedule($inspection, $inspector->id, now()->addDay()->toDateTimeString());
        $this->inspections->changeStatus($inspection->refresh(), 'in_progress');

        $items = InspectionChecklistItem::where('is_active', true)->get();
        $scores = $items->mapWithKeys(fn ($item) => [$item->id => ['score' => 99]])->all();

        $inspection = $this->inspections->complete($inspection->refresh(), $scores);

        $this->assertSame('100.00', $inspection->overall_score);
    }

    public function test_completing_a_report_does_not_by_itself_verify_the_listing(): void
    {
        $inspection = $this->completed(10);

        $this->assertFalse((bool) $inspection->listing->refresh()->is_verified);
        $this->assertFalse($inspection->isApproved());
    }

    public function test_approval_is_what_earns_the_verified_badge(): void
    {
        $approver = User::factory()->create(['user_type' => 'staff']);
        $approver->assignRole('admin');
        $this->actingAs($approver);

        $inspection = $this->inspections->approve($this->completed(9));

        $this->assertTrue((bool) $inspection->listing->refresh()->is_verified);
        $this->assertSame($approver->id, $inspection->approved_by);
    }

    public function test_an_unfinished_report_cannot_be_approved(): void
    {
        $inspection = $this->inspections->request($this->listing());

        $this->expectException(\InvalidArgumentException::class);

        $this->inspections->approve($inspection);
    }

    public function test_a_completed_report_carries_a_valuation_band(): void
    {
        $inspection = $this->completed(10);

        $this->assertNotNull($inspection->valuation_min);
        $this->assertGreaterThan((float) $inspection->valuation_min, (float) $inspection->valuation_max);
    }
}
