<?php

namespace Tests\Feature\Catalog;

use App\Domain\Catalog\Services\CompareService;
use App\Models\Product;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, CatalogMasterSeeder::class, DemoProductSeeder::class]);
    }

    public function test_a_guest_can_build_a_comparison(): void
    {
        $products = Product::limit(2)->get();

        foreach ($products as $product) {
            $this->postJson(route('ajax.compare.add'), ['product_id' => $product->id])
                ->assertOk()->assertJsonPath('status', 'ok');
        }

        $this->assertSame(2, count(session(CompareService::SESSION_KEY)));
    }

    public function test_it_refuses_a_fifth_model(): void
    {
        foreach (Product::limit(4)->get() as $product) {
            $this->postJson(route('ajax.compare.add'), ['product_id' => $product->id])->assertOk();
        }

        $fifth = Product::orderByDesc('id')->first();

        $this->postJson(route('ajax.compare.add'), ['product_id' => $fifth->id])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_adding_the_same_model_twice_is_harmless(): void
    {
        $product = Product::firstOrFail();

        $this->postJson(route('ajax.compare.add'), ['product_id' => $product->id])->assertOk();
        $this->postJson(route('ajax.compare.add'), ['product_id' => $product->id])->assertOk();

        $this->assertSame(1, count(session(CompareService::SESSION_KEY)));
    }

    public function test_the_comparison_page_is_a_real_indexable_url(): void
    {
        $products = Product::with('brand')->limit(2)->get();
        $slug = app(CompareService::class)->slugFor($products);

        $this->get("/compare/{$slug}")
            ->assertOk()
            ->assertSee($products[0]->name)
            ->assertSee($products[1]->name);
    }

    public function test_a_comparison_of_one_model_is_a_404(): void
    {
        $product = Product::with('brand')->firstOrFail();

        $this->get('/compare/'.$product->brand->slug.'-'.$product->slug)->assertNotFound();
    }

    public function test_the_matrix_flags_rows_where_the_models_differ(): void
    {
        $service = app(CompareService::class);
        $products = Product::with(['brand', 'specValues.attribute.group'])
            ->whereHas('specValues')->limit(2)->get();

        $matrix = $service->matrix($products);

        $this->assertNotEmpty($matrix);

        $rows = collect($matrix)->flatMap(fn ($group) => $group['rows']);

        $this->assertTrue($rows->contains(fn ($row) => $row['differs'] === true),
            'Expected at least one differing row between two different models.');

        // Every row must carry exactly one value per product.
        foreach ($rows as $row) {
            $this->assertCount($products->count(), $row['values']);
        }
    }

    public function test_removing_a_model_updates_the_session(): void
    {
        $product = Product::firstOrFail();

        $this->postJson(route('ajax.compare.add'), ['product_id' => $product->id])->assertOk();
        $this->postJson(route('ajax.compare.remove'), ['product_id' => $product->id])->assertOk();

        $this->assertEmpty(session(CompareService::SESSION_KEY));
    }
}
