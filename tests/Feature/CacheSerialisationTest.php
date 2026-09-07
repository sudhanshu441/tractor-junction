<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\State;
use Database\Seeders\CatalogMasterSeeder;
use Database\Seeders\DemoProductSeeder;
use Database\Seeders\GeographySeeder;
use Database\Seeders\MarketplaceMasterSeeder;
use Database\Seeders\NotificationTemplateSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Everything we cache has to survive a serialise/unserialise round-trip.
 *
 * The array cache driver used in tests never serialises, so a cached Eloquent
 * Collection passes locally and dies in production as an incomplete class.
 * These tests force the database store, which does round-trip.
 */
class CacheSerialisationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            GeographySeeder::class, SettingSeeder::class, NotificationTemplateSeeder::class,
            CatalogMasterSeeder::class, DemoProductSeeder::class, MarketplaceMasterSeeder::class,
        ]);

        config(['cache.default' => 'database']);
        Cache::flush();
    }

    public static function cachedEndpoints(): array
    {
        return [
            'home' => ['/'],
            'listing' => ['/tractors'],
            'used grid' => ['/used'],
            'search' => ['/search?q=mahindra'],
            'sell wizard' => ['/sell'],
        ];
    }

    #[DataProvider('cachedEndpoints')]
    public function test_a_page_renders_on_both_a_cold_and_a_warm_cache(string $url): void
    {
        $this->get($url)->assertOk();   // cold: writes the cache
        $this->get($url)->assertOk();   // warm: reads it back through unserialize()
    }

    public function test_the_geo_endpoints_survive_a_warm_cache(): void
    {
        $state = State::where('code', 'UP')->firstOrFail();
        $district = $state->districts()->firstOrFail();

        foreach ([1, 2] as $pass) {
            $this->getJson(route('ajax.geo.states'))->assertOk()->assertJsonPath('status', 'ok');
            $this->getJson(route('ajax.geo.districts', $state))->assertOk()->assertJsonPath('status', 'ok');
            $this->getJson(route('ajax.geo.cities', $district))->assertOk()->assertJsonPath('status', 'ok');
        }
    }

    public function test_settings_survive_a_warm_cache(): void
    {
        $this->assertSame('Krishi Junction', Setting::get('site_name'));

        Setting::all2();   // warm

        $this->assertSame('Krishi Junction', Setting::get('site_name'));
    }
}
