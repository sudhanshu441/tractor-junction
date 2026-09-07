<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Services\PriceService;
use App\Domain\Catalog\Services\SearchService;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\State;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceAndSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, CatalogMasterSeeder::class, DemoProductSeeder::class]);
    }

    public function test_on_road_price_is_calculated_not_stored_by_hand(): void
    {
        $product = Product::firstOrFail();

        $price = app(PriceService::class)->save($product, [
            'state_id' => null,
            'ex_showroom' => 700000,
            'rto_charges' => 24000,
            'insurance_amount' => 18000,
            'other_charges' => 2000,
        ]);

        $this->assertSame(744000.0, (float) $price->on_road_price);
    }

    public function test_a_state_price_beats_the_national_fallback(): void
    {
        $product = Product::firstOrFail();
        $state = State::where('code', 'UP')->firstOrFail();
        $service = app(PriceService::class);

        $service->save($product, ['state_id' => null, 'ex_showroom' => 700000]);
        $service->save($product, ['state_id' => $state->id, 'ex_showroom' => 725000]);

        $product->refresh()->load('prices');

        $this->assertSame(725000.0, (float) $service->resolve($product, $state->id)->ex_showroom);
        $this->assertSame(700000.0, (float) $service->resolve($product, null)->ex_showroom);
    }

    public function test_a_price_change_is_recorded_in_history(): void
    {
        $product = Product::firstOrFail();
        $service = app(PriceService::class);

        $service->save($product, ['state_id' => null, 'ex_showroom' => 700000]);
        $before = ProductPriceHistory::where('product_id', $product->id)->count();

        $service->save($product, ['state_id' => null, 'ex_showroom' => 750000]);

        $this->assertSame($before + 1, ProductPriceHistory::where('product_id', $product->id)->count());
    }

    public function test_resaving_the_same_price_does_not_pad_the_history(): void
    {
        $product = Product::firstOrFail();
        $service = app(PriceService::class);

        $service->save($product, ['state_id' => null, 'ex_showroom' => 700000]);
        $count = ProductPriceHistory::where('product_id', $product->id)->count();

        $service->save($product, ['state_id' => null, 'ex_showroom' => 700000]);

        $this->assertSame($count, ProductPriceHistory::where('product_id', $product->id)->count());
    }

    public function test_prices_are_formatted_the_way_this_market_reads_them(): void
    {
        $this->assertSame('₹7.30 Lakh', PriceService::inLakh(730000));
        $this->assertSame('₹1.25 Cr', PriceService::inLakh(12500000));
        $this->assertSame('—', PriceService::inLakh(null));
        $this->assertSame('₹7.30 - ₹7.65 Lakh', PriceService::range(730000, 765000));
    }

    public function test_search_resolves_hindi_and_misspelled_brand_names(): void
    {
        $search = app(SearchService::class);

        $this->assertSame('mahindra', $search->normalise('mhindra'));
        $this->assertSame('mahindra', $search->normalise('महिंद्रा'));
        $this->assertGreaterThan(0, $search->products('mhindra')->count());
        $this->assertGreaterThan(0, $search->products('महिंद्रा')->count());
    }

    public function test_search_logs_the_term_for_analytics(): void
    {
        $this->get('/search?q=swaraj')->assertOk();

        $this->assertDatabaseHas('search_logs', ['term' => 'swaraj']);
    }

    public function test_type_ahead_needs_at_least_two_characters(): void
    {
        $this->getJson(route('ajax.search.suggest', ['q' => 'm']))
            ->assertOk()
            ->assertJsonPath('data.products', [])
            ->assertJsonPath('data.brands', []);

        $suggestions = $this->getJson(route('ajax.search.suggest', ['q' => 'mahindra']))
            ->assertOk()->json('data');

        $this->assertNotEmpty($suggestions['products']);
    }
}
