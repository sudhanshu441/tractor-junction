<?php

namespace Tests\Feature\Seo;

use App\Domain\Seo\Services\JsonLd;
use App\Domain\Seo\Services\SeoService;
use App\Models\NotFoundLog;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\SeoMeta;
use App\Models\User;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class, GeographySeeder::class, SettingSeeder::class,
            NotificationTemplateSeeder::class, CatalogMasterSeeder::class,
            DemoProductSeeder::class, MarketplaceMasterSeeder::class, ContentSeeder::class,
        ]);
    }

    private function product(): Product
    {
        return Product::with('brand')->firstOrFail();
    }

    public function test_a_page_always_gets_a_title_and_a_description(): void
    {
        $seo = app(SeoService::class)->for($this->product(), 'product', [], [
            ':name' => 'Mahindra 575', ':price' => '₹7.2 Lakh', ':hp' => 47,
        ]);

        $this->assertStringContainsString('Mahindra 575', $seo['title']);
        $this->assertStringContainsString('47 HP', $seo['description']);
        $this->assertSame('index,follow', $seo['robots']);
    }

    public function test_an_editors_override_beats_the_template(): void
    {
        $product = $this->product();

        SeoMeta::create([
            'seoable_type' => $product->getMorphClass(),
            'seoable_id' => $product->id,
            'locale' => 'en',
            'meta_title' => 'Hand written title',
        ]);

        $seo = app(SeoService::class)->for($product->fresh(), 'product', [], [':name' => 'Ignored']);

        $this->assertSame('Hand written title', $seo['title']);
        // The description was not overridden, so it still comes from the template.
        $this->assertStringContainsString('Ignored', $seo['description']);
    }

    public function test_a_token_nobody_supplied_never_leaks_into_a_title(): void
    {
        $seo = app(SeoService::class)->for(null, 'used_listing', [], [':title' => 'Swaraj 744']);

        $this->assertStringNotContainsString(':', $seo['title']);
        $this->assertStringNotContainsString(':city', $seo['description']);
    }

    public function test_a_long_title_is_trimmed_without_a_dangling_separator(): void
    {
        $seo = app(SeoService::class)->for(null, 'page', [
            'title' => str_repeat('Mahindra tractor price list ', 10).'| Krishi Junction',
        ]);

        $this->assertLessThanOrEqual(SeoService::TITLE_MAX, mb_strlen($seo['title']));
        $this->assertDoesNotMatchRegularExpression('/[|\-–—,:]$/', $seo['title']);
    }

    public function test_a_product_page_emits_valid_structured_data(): void
    {
        $product = $this->product();

        $response = $this->get(route('products.show', [$product->brand->slug, $product->slug]))->assertOk();

        $schemas = $this->schemasIn($response->getContent());
        $types = array_column($schemas, '@type');

        $this->assertContains('Product', $types);
        $this->assertContains('BreadcrumbList', $types);
    }

    public function test_a_price_is_only_claimed_when_we_hold_one(): void
    {
        $product = $this->product();
        $product->forceFill(['price_min' => null, 'price_max' => null])->save();

        $schema = app(JsonLd::class)->product($product->fresh(), null);

        $this->assertArrayNotHasKey('offers', $schema);
    }

    public function test_an_empty_faq_page_emits_no_schema_at_all(): void
    {
        $this->assertNull(app(JsonLd::class)->faq([]));
        $this->assertNull(app(JsonLd::class)->faq([['question' => 'Q', 'answer' => '']]));
    }

    public function test_every_page_declares_both_languages(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="en"', $html);
        $this->assertStringContainsString('hreflang="hi"', $html);
        $this->assertStringContainsString('hreflang="x-default"', $html);
    }

    public function test_a_redirect_sends_the_visitor_on(): void
    {
        Redirect::create(['from_url' => '/old-tractor-page', 'to_url' => '/tractors', 'status_code' => 301, 'is_active' => true]);

        $this->get('/old-tractor-page')->assertRedirect('/tractors');
    }

    public function test_a_missing_page_is_logged_so_a_redirect_can_be_written(): void
    {
        $this->get('/a-page-that-never-existed')->assertNotFound();

        $this->assertDatabaseHas('not_found_logs', ['url' => '/a-page-that-never-existed']);
    }

    public function test_the_404_log_counts_hits_instead_of_growing_a_row_each_time(): void
    {
        $this->get('/gone');
        $this->get('/gone');
        $this->get('/gone');

        $this->assertSame(1, NotFoundLog::where('url', '/gone')->count());
        $this->assertSame(3, NotFoundLog::where('url', '/gone')->value('hit_count'));
    }

    public function test_robots_points_at_the_sitemap_and_keeps_crawlers_out_of_private_areas(): void
    {
        $this->app['env'] = 'production';

        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Sitemap: '.route('sitemap.index'), $body);
        $this->assertStringContainsString('Disallow: /admin', $body);
        $this->assertStringContainsString('Disallow: /account', $body);
    }

    public function test_a_staging_site_is_never_indexable(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString('Disallow: /', $body);
        $this->assertStringNotContainsString('Allow: /', $body);
    }

    public function test_the_sitemap_index_lists_chunked_section_files(): void
    {
        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringContainsString('<sitemapindex', $xml);
        $this->assertStringContainsString('products.xml', $xml);
        $this->assertNotFalse(simplexml_load_string($xml), 'The sitemap index must be valid XML.');
    }

    public function test_an_editor_page_cannot_shadow_a_website_address(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->post(route('admin.pages.store'), [
                'title' => 'Tractors', 'slug' => 'tractors', 'template' => 'default', 'is_active' => 1,
            ])
            ->assertStatus(422);

        $this->assertDatabaseMissing('pages', ['slug' => 'tractors']);
    }

    /** @return array<int, array<string, mixed>> */
    private function schemasIn(string $html): array
    {
        preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $matches);

        return array_values(array_filter(array_map(
            fn ($json) => json_decode(trim($json), true),
            $matches[1],
        )));
    }
}
