<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Services\FacetService;
use App\Domain\Catalog\Services\ProductService;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductFilterCache;
use App\Models\SpecAttribute;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, CatalogMasterSeeder::class, DemoProductSeeder::class]);
    }

    public function test_the_filter_cache_is_populated_for_every_product(): void
    {
        $this->assertSame(Product::count(), ProductFilterCache::count());
        $this->assertGreaterThan(0, ProductFilterCache::whereNotNull('hp')->count());
    }

    public function test_the_engine_hp_specification_is_the_source_of_truth_for_hp(): void
    {
        $product = Product::whereNotNull('hp_min')->firstOrFail();
        $attribute = SpecAttribute::where('slug', 'engine-hp')->firstOrFail();

        app(ProductService::class)
            ->syncSpecs($product, [$attribute->id => 99]);

        // The spec drives both the denormalised range and the filter cache, so the
        // Basics tab and the Specifications tab can never disagree.
        $this->assertSame(99.0, (float) $product->fresh()->hp_min);
        $this->assertSame(99.0, (float) $product->filterCache()->first()->hp);
    }

    public function test_saving_a_product_rebuilds_its_filter_cache_row(): void
    {
        $product = Product::whereNotNull('price_min')->firstOrFail();

        $product->update(['price_min' => 1234567]);

        $this->assertSame(1234567.0, (float) $product->filterCache()->first()->price);
    }

    public function test_deleting_a_product_removes_its_filter_cache_row(): void
    {
        $product = Product::firstOrFail();
        $id = $product->id;

        $product->forceDelete();

        $this->assertDatabaseMissing('product_filter_cache', ['product_id' => $id]);
    }

    public function test_the_ajax_endpoint_returns_the_standard_envelope(): void
    {
        $this->getJson(route('ajax.products.filter', ['type' => 'tractor']))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['status', 'data' => ['total', 'summary', 'grid', 'pagination', 'facets_html', 'query']]);
    }

    public function test_ajax_and_full_page_filtering_agree(): void
    {
        $filters = ['type' => 'tractor', 'hp_min' => 40, 'hp_max' => 50];

        $pageTotal = $this->get('/tractors?'.http_build_query($filters))
            ->assertOk()->viewData('products')->total();

        $ajaxTotal = $this->getJson(route('ajax.products.filter', $filters))
            ->assertOk()->json('data.total');

        $this->assertSame($pageTotal, $ajaxTotal);
    }

    public function test_filtering_by_brand_narrows_the_results(): void
    {
        $brand = Brand::where('slug', 'mahindra')->firstOrFail();

        $response = $this->getJson(route('ajax.products.filter', [
            'type' => 'tractor',
            'brand' => [$brand->id],
        ]))->assertOk();

        $this->assertSame(
            Product::where('brand_id', $brand->id)->active()
                ->whereHas('category', fn ($q) => $q->where('type', 'tractor'))->count(),
            $response->json('data.total'),
        );
    }

    public function test_facet_counts_respect_the_other_active_filters(): void
    {
        $unfiltered = app(FacetService::class)->for(null, [], 'tractor');
        $filtered = app(FacetService::class)
            ->for(null, ['hp_min' => 40, 'hp_max' => 50], 'tractor');

        $this->assertLessThan($unfiltered['total'], $filtered['total']);

        // Every brand offered under a filter must still return at least one result.
        foreach ($filtered['brands'] as $brandFacet) {
            if ($brandFacet['count'] > 0) {
                $total = $this->getJson(route('ajax.products.filter', [
                    'type' => 'tractor', 'hp_min' => 40, 'hp_max' => 50, 'brand' => [$brandFacet['id']],
                ]))->json('data.total');

                $this->assertSame($brandFacet['count'], $total, "Facet count wrong for {$brandFacet['name']}");
            }
        }
    }

    public function test_sorting_by_price_orders_ascending(): void
    {
        $products = $this->get('/tractors?sort=price-low')->assertOk()->viewData('products');

        $prices = collect($products->items())->pluck('price_min')->filter()->values();

        $this->assertEquals($prices->sort()->values()->all(), $prices->all());
    }
}
