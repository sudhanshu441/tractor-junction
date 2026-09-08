<?php

namespace Tests\Feature\Content;

use App\Models\LanguageLine;
use App\Models\User;
use App\Support\Locale;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\ContentSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class MultilingualTest extends TestCase
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

    public function test_the_hindi_site_serves_the_same_pages(): void
    {
        foreach (['/hi', '/hi/tractors', '/hi/news', '/hi/faq', '/hi/contact'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_a_hindi_page_declares_hindi_as_its_language(): void
    {
        $html = $this->get('/hi')->assertOk()->getContent();

        $this->assertStringContainsString('<html lang="hi"', $html);
    }

    public function test_hindi_copy_actually_appears(): void
    {
        $this->get('/hi')->assertOk()->assertSee('अपना ट्रैक्टर बेचें', false);
    }

    public function test_links_on_a_hindi_page_stay_in_hindi(): void
    {
        $html = $this->get('/hi/news')->assertOk()->getContent();

        // Every internal news link must keep the reader inside /hi.
        preg_match_all('~href="[^"]*?/news/[^"]+"~', $html, $matches);

        $this->assertNotEmpty($matches[0]);

        foreach ($matches[0] as $href) {
            $this->assertStringContainsString('/hi/news/', $href);
        }
    }

    public function test_the_english_url_shape_never_changed(): void
    {
        $this->assertSame(url('/tractors'), route('catalog.tractors.index'));
        $this->get('/tractors')->assertOk();
    }

    public function test_an_admin_url_has_no_hindi_twin_and_is_left_alone(): void
    {
        $this->app->setLocale('hi');

        $this->assertStringNotContainsString('/hi/admin', route('admin.dashboard'));
    }

    public function test_an_editor_override_beats_the_shipped_translation(): void
    {
        $this->app->setLocale('hi');

        $shipped = __('Dealers');

        LanguageLine::create(['locale' => 'hi', 'group' => '*', 'key' => 'Dealers', 'value' => 'विक्रेता']);
        LanguageLine::flush('hi');

        // A fresh translator picks the override up, as the next request would.
        // The application is not refreshed: that would roll back the row.
        $this->app->forgetInstance('translator');
        $this->app->forgetInstance('translation.loader');
        Lang::clearResolvedInstances();
        $this->app->setLocale('hi');

        $this->assertNotSame($shipped, __('Dealers'));
        $this->assertSame('विक्रेता', __('Dealers'));
    }

    public function test_clearing_an_override_returns_to_the_shipped_wording(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff']);
        $admin->assignRole('super-admin');

        LanguageLine::create(['locale' => 'hi', 'group' => '*', 'key' => 'Dealers', 'value' => 'विक्रेता']);

        $this->actingAs($admin)
            ->postJson(route('admin.seo.translations.save'), ['locale' => 'hi', 'key' => 'Dealers', 'value' => ''])
            ->assertOk();

        $this->assertSame(0, LanguageLine::where('key', 'Dealers')->count());
    }

    public function test_an_unsupported_locale_is_refused_rather_than_stored(): void
    {
        $admin = User::factory()->create(['user_type' => 'staff']);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)
            ->postJson(route('admin.seo.translations.save'), ['locale' => 'fr', 'key' => 'Dealers', 'value' => 'Concessionnaires'])
            ->assertStatus(422);
    }

    public function test_the_locale_helper_rewrites_a_url_without_losing_the_query(): void
    {
        $this->get('/tractors?hp_min=40');

        $this->assertStringContainsString('/hi/tractors', Locale::urlFor('hi'));
        $this->assertStringContainsString('hp_min=40', Locale::urlFor('hi'));
    }

    public function test_both_languages_are_declared_on_a_hindi_page_too(): void
    {
        $html = $this->get('/hi/faq')->assertOk()->getContent();

        $this->assertStringContainsString('hreflang="en" href="'.url('/faq'), $html);
        $this->assertStringContainsString('hreflang="hi" href="'.url('/hi/faq'), $html);
    }
}
