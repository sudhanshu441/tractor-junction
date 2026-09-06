<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\District;
use App\Models\State;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Loads the authoritative district (and optional city) list from a CSV, so the
 * starter data in the seeder can be replaced without a code change.
 *
 *   php artisan geo:import districts.csv
 *
 * CSV header: state_code,district_name[,city_name]
 */
class ImportGeography extends Command
{
    protected $signature = 'geo:import {file : Path to the CSV file} {--fresh : Remove districts and cities first}';

    protected $description = 'Import districts and cities from a CSV into the geography master';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! is_readable($path)) {
            $this->error("Cannot read {$path}");

            return self::FAILURE;
        }

        if ($this->option('fresh') && $this->confirm('Delete all existing districts and cities?', false)) {
            City::query()->delete();
            District::query()->delete();
        }

        $states = State::pluck('id', 'code');
        $handle = fopen($path, 'rb');
        $header = array_map(fn ($h) => Str::snake(trim((string) $h)), (array) fgetcsv($handle));

        $districts = 0;
        $cities = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $row = array_combine($header, array_pad($row, count($header), null));
            $code = strtoupper(trim((string) ($row['state_code'] ?? '')));
            $districtName = trim((string) ($row['district_name'] ?? ''));

            if (! $districtName || ! isset($states[$code])) {
                $skipped++;

                continue;
            }

            $district = District::updateOrCreate(
                ['state_id' => $states[$code], 'slug' => Str::slug($districtName)],
                ['name' => $districtName, 'is_active' => true],
            );

            if ($district->wasRecentlyCreated) {
                $districts++;
            }

            $cityName = trim((string) ($row['city_name'] ?? '')) ?: $districtName;

            $city = City::updateOrCreate(
                ['district_id' => $district->id, 'slug' => Str::slug($cityName)],
                ['state_id' => $states[$code], 'name' => $cityName, 'is_active' => true],
            );

            if ($city->wasRecentlyCreated) {
                $cities++;
            }
        }

        fclose($handle);
        Cache::flush();

        $this->info("Imported {$districts} districts and {$cities} cities. Skipped {$skipped} rows.");

        return self::SUCCESS;
    }
}
