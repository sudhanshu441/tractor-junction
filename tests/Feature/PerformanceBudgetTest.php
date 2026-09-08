<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DemoListingSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\FinanceMasterSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * A query budget for the public pages.
 *
 * N+1 queries do not fail a test, they just make a page slower every time the
 * catalogue grows — which is exactly the kind of regression nobody notices
 * until a farmer on a 3G connection gives up. These ceilings are deliberately
 * generous; raising one should be a decision, not an accident.
 */
class PerformanceBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, CatalogMasterSeeder::class, DemoProductSeeder::class,
            MarketplaceMasterSeeder::class, FinanceMasterSeeder::class, DemoListingSeeder::class,
            ContentSeeder::class,
        ]);

        // Warm first: the budget measures a steady-state page, not a cold boot.
        $this->artisan('kj:warm');
    }

    public static function pages(): array
    {
        return [
            'home' => ['/', 40],
            'tractor listing' => ['/tractors', 40],
            'used grid' => ['/used', 40],
            'news' => ['/news', 25],
            'faq' => ['/faq', 20],
            'dealers' => ['/dealers', 30],
            'finance hub' => ['/loan', 25],
            'insurance' => ['/tractor-insurance', 25],
        ];
    }

    #[DataProvider('pages')]
    public function test_a_page_stays_inside_its_query_budget(string $url, int $budget): void
    {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get($url)->assertOk();

        $this->assertLessThanOrEqual(
            $budget,
            $queries,
            "{$url} ran {$queries} queries, over its budget of {$budget}. "
            .'Look for a missing eager load before raising this number.',
        );
    }

    public function test_a_model_page_does_not_query_per_specification(): void
    {
        $product = Product::with('brand')->firstOrFail();

        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get(route('products.show', [$product->brand->slug, $product->slug]))->assertOk();

        $this->assertLessThanOrEqual(50, $queries, "The model page ran {$queries} queries.");
    }

    public function test_the_listing_grid_does_not_query_per_card(): void
    {
        $queries = 0;
        DB::listen(function () use (&$queries) {
            $queries++;
        });

        $this->get('/used')->assertOk();
        $withDefault = $queries;

        // Doubling the page size must not roughly double the query count.
        $queries = 0;
        $this->get('/used?per_page=48')->assertOk();

        $this->assertLessThanOrEqual(
            $withDefault + 10,
            $queries,
            'Query count grows with the number of cards — that is an N+1.',
        );
    }
}
