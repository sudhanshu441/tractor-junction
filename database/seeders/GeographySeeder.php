<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;
use App\Models\District;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GeographySeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/india-states.php');

        $india = Country::updateOrCreate(
            ['iso2' => 'IN'],
            ['name' => 'India', 'phone_code' => '+91', 'currency' => 'INR', 'is_active' => true],
        );

        $stateIds = [];

        foreach ($data['states'] as $i => $row) {
            $state = State::updateOrCreate(
                ['slug' => Str::slug($row['name'])],
                [
                    'country_id' => $india->id,
                    'name' => $row['name'],
                    'code' => $row['code'],
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );

            $stateIds[$row['code']] = $state->id;
        }

        $districtCount = 0;

        foreach ($data['districts'] as $stateCode => $districts) {
            if (! isset($stateIds[$stateCode])) {
                continue;
            }

            foreach ($districts as $name) {
                $district = District::updateOrCreate(
                    ['state_id' => $stateIds[$stateCode], 'slug' => Str::slug($name)],
                    ['name' => $name, 'is_active' => true],
                );

                // Every district gets its headquarters city so city selects are never empty.
                City::updateOrCreate(
                    ['district_id' => $district->id, 'slug' => Str::slug($name)],
                    [
                        'state_id' => $stateIds[$stateCode],
                        'name' => $name,
                        'is_active' => true,
                    ],
                );

                $districtCount++;
            }
        }

        Cache::forget('geo.states');

        $this->command?->info('Geography: 1 country · '.count($stateIds)." states · {$districtCount} districts/cities");
        $this->command?->warn('Districts are starter data for 8 states. Run `php artisan geo:import <csv>` for the full national dataset.');
    }
}
