<?php

namespace Tests\Feature\Marketplace;

use App\Domain\Marketplace\Services\ValuationService;
use App\Models\District;
use App\Models\Product;
use App\Models\UsedListing;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ValuationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            GeographySeeder::class, CatalogMasterSeeder::class,
            DemoProductSeeder::class, MarketplaceMasterSeeder::class,
        ]);
    }

    private function listing(array $overrides = []): UsedListing
    {
        $product = Product::whereNotNull('price_min')->firstOrFail();
        $district = District::with('state')->firstOrFail();

        $listing = new UsedListing([
            'category_id' => $product->category_id,
            'brand_id' => $product->brand_id,
            'product_id' => $product->id,
            'manufacturing_year' => 2020,
            'engine_hours' => 1500,
            'condition' => 'good',
            'expected_price' => 400000,
            'state_id' => $district->state_id,
            'district_id' => $district->id,
            ...$overrides,
        ]);

        $listing->setRelation('state', $district->state);

        return $listing;
    }

    /**
     * Regression: the rule set used to be cached as a Collection, which a
     * serialising cache store returns as an incomplete class — fatal on the
     * moderation queue. The array cache driver never round-trips, so this test
     * forces a store that does.
     */
    public function test_valuation_survives_a_serialising_cache_store(): void
    {
        config(['cache.default' => 'database']);
        Cache::flush();

        $listing = $this->listing();

        $first = app(ValuationService::class)->estimate($listing);
        $this->assertNotNull($first);

        // Second call reads the cached value back through unserialize().
        $second = app(ValuationService::class)->estimate($listing);

        $this->assertSame($first['point'], $second['point']);
    }

    public function test_it_returns_a_band_around_the_estimate(): void
    {
        $estimate = app(ValuationService::class)->estimate($this->listing());

        $this->assertLessThan($estimate['point'], $estimate['min']);
        $this->assertGreaterThan($estimate['point'], $estimate['max']);
    }

    public function test_an_older_machine_is_worth_less(): void
    {
        $service = app(ValuationService::class);

        $newer = $service->estimate($this->listing(['manufacturing_year' => 2023]));
        $older = $service->estimate($this->listing(['manufacturing_year' => 2014]));

        $this->assertLessThan($newer['point'], $older['point']);
    }

    public function test_more_hours_and_worse_condition_lower_the_estimate(): void
    {
        $service = app(ValuationService::class);

        $good = $service->estimate($this->listing(['engine_hours' => 800, 'condition' => 'excellent']));
        $worn = $service->estimate($this->listing(['engine_hours' => 6000, 'condition' => 'needs_repair']));

        $this->assertLessThan($good['point'], $worn['point']);
    }

    public function test_it_returns_nothing_without_a_matched_catalogue_model(): void
    {
        $this->assertNull(app(ValuationService::class)->estimate($this->listing(['product_id' => null])));
    }

    public function test_it_flags_a_price_far_above_the_band(): void
    {
        $service = app(ValuationService::class);
        $listing = $this->listing();

        $estimate = $service->estimate($listing);
        $listing->expected_price = $estimate['max'] * 1.4;

        $assessment = $service->assess($listing);

        $this->assertSame('above', $assessment['status']);
        $this->assertGreaterThan(20, $assessment['deviation_percent']);
    }

    public function test_a_price_inside_the_band_is_fair(): void
    {
        $service = app(ValuationService::class);
        $listing = $this->listing();

        $listing->expected_price = $service->estimate($listing)['point'];

        $this->assertSame('fair', $service->assess($listing)['status']);
    }
}
