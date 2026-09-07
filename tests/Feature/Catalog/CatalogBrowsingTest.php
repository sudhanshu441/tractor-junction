<?php

namespace Tests\Feature\Catalog;

use App\Models\Brand;
use App\Models\Product;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogBrowsingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, CatalogMasterSeeder::class, DemoProductSeeder::class]);
    }

    public function test_the_listing_page_renders_server_side(): void
    {
        $this->get('/tractors')
            ->assertOk()
            ->assertSee('Tractors')
            ->assertSee('Mahindra', false);
    }

    public function test_a_model_detail_page_shows_price_and_specs(): void
    {
        $product = Product::with('brand')->where('slug', '575-di-xp-plus')->firstOrFail();

        $this->get("/tractors/{$product->brand->slug}/{$product->slug}")
            ->assertOk()
            ->assertSee('575 DI XP Plus')
            ->assertSee('Engine HP')
            ->assertSee('On-road');
    }

    public function test_an_unpublished_product_is_not_reachable(): void
    {
        $product = Product::with('brand')->firstOrFail();
        $product->update(['is_active' => false]);

        $this->get("/tractors/{$product->brand->slug}/{$product->slug}")->assertNotFound();
    }

    public function test_viewing_a_product_increments_its_view_count(): void
    {
        $product = Product::with('brand')->firstOrFail();
        $before = $product->view_count;

        $this->get("/tractors/{$product->brand->slug}/{$product->slug}")->assertOk();

        $this->assertSame($before + 1, $product->fresh()->view_count);
    }

    public function test_the_hp_band_page_only_shows_models_in_range(): void
    {
        $response = $this->get('/tractors/hp/40-50-hp')->assertOk();

        $products = $response->viewData('products');

        $this->assertGreaterThan(0, $products->total());

        foreach ($products as $product) {
            $this->assertGreaterThanOrEqual(40, (float) $product->hp_min);
            $this->assertLessThanOrEqual(50, (float) $product->hp_min);
        }
    }

    public function test_an_unknown_band_is_a_404(): void
    {
        $this->get('/tractors/hp/900-1000-hp')->assertNotFound();
    }

    public function test_the_brand_page_only_shows_that_brand(): void
    {
        $brand = Brand::where('slug', 'swaraj')->firstOrFail();

        $response = $this->get('/tractors/brand/swaraj')->assertOk();

        foreach ($response->viewData('products') as $product) {
            $this->assertSame($brand->id, $product->brand_id);
        }
    }

    public function test_the_state_price_list_renders(): void
    {
        $this->get('/tractors/price-list/uttar-pradesh')
            ->assertOk()
            ->assertSee('Uttar Pradesh');
    }

    public function test_implements_use_the_same_listing_template(): void
    {
        $response = $this->get('/implements')->assertOk();

        foreach ($response->viewData('products') as $product) {
            $this->assertSame('implement', $product->category->type);
        }
    }
}
