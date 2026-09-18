<?php

namespace Tests\Feature\Catalog;

use App\Models\Product;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The stand-in photograph shows a branded machine, so every guard that stops it
 * being read as a claim about the model on the page is load-bearing rather than
 * cosmetic. See docs/19-IMAGE-SOURCING.md.
 */
class PlaceholderPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([GeographySeeder::class, CatalogMasterSeeder::class, DemoProductSeeder::class]);
    }

    private function productWithoutPhoto(): Product
    {
        return Product::with('brand')->whereDoesntHave('media')->firstOrFail();
    }

    private function url(Product $product): string
    {
        return "/tractors/{$product->brand->slug}/{$product->slug}";
    }

    public function test_the_stand_in_files_are_shipped(): void
    {
        foreach (['tractor-640.webp', 'tractor-1280.webp', 'tractor.jpg'] as $file) {
            $this->assertFileExists(public_path('assets/brand/machines/'.$file));
        }

        // Referenced by SeoService for every page without its own image, so a
        // missing file is a broken social card on every share.
        $this->assertFileExists(public_path('assets/brand/og-default.jpg'));
    }

    public function test_a_model_without_a_photo_shows_the_stand_in(): void
    {
        $product = $this->productWithoutPhoto();

        $this->get($this->url($product))
            ->assertOk()
            ->assertSee('assets/brand/machines/tractor-640.webp', false);
    }

    public function test_the_stand_in_says_it_is_not_this_machine(): void
    {
        $product = $this->productWithoutPhoto();
        $response = $this->get($this->url($product))->assertOk();

        // For a screen reader and an image crawler.
        $response->assertSee('No photograph uploaded yet for '.$product->full_name, false);

        // And for a buyer looking at a full-size photograph beside a price.
        $response->assertSee('no picture of this machine has been uploaded yet', false);
    }

    public function test_the_stand_in_is_never_published_as_the_model_photo(): void
    {
        $product = $this->productWithoutPhoto();
        $html = $this->get($this->url($product))->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
        $this->assertNotEmpty($matches[1], 'The product page should carry JSON-LD.');

        foreach ($matches[1] as $block) {
            $data = json_decode(html_entity_decode($block), true, 512, JSON_THROW_ON_ERROR);

            $this->assertArrayNotHasKey(
                'image',
                array_filter($data, fn ($value) => $value !== null),
                'JSON-LD must not offer the stand-in as a photograph of this model.',
            );
        }
    }
}
