<?php

namespace Tests\Feature\Ajax;

use App\Models\District;
use App\Models\State;
use Database\Seeders\GeographySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeoEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GeographySeeder::class);
    }

    public function test_it_lists_states(): void
    {
        $this->getJson(route('ajax.geo.states'))
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonCount(36, 'data');
    }

    public function test_it_lists_districts_for_a_state(): void
    {
        $state = State::where('code', 'UP')->firstOrFail();

        $response = $this->getJson(route('ajax.geo.districts', $state))->assertOk();

        $this->assertGreaterThan(50, count($response->json('data')));
        $this->assertContains('Sitapur', array_column($response->json('data'), 'name'));
    }

    public function test_it_lists_cities_for_a_district(): void
    {
        $district = District::where('slug', 'sitapur')->firstOrFail();

        $this->getJson(route('ajax.geo.cities', $district))
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
}
