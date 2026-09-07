<?php

namespace Database\Seeders;

use App\Domain\Catalog\Services\PriceService;
use App\Domain\Catalog\Services\ProductService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SpecAttribute;
use App\Models\State;
use Illuminate\Database\Seeder;

/**
 * Demo catalogue so the site is explorable before the content team loads real data.
 *
 * Figures are representative of the Indian market but are SAMPLE DATA — they are
 * not manufacturer-supplied and must be replaced before launch. Real data comes
 * in through `php artisan catalog:import`.
 */
class DemoProductSeeder extends Seeder
{
    /** brand, model, category, hp, cylinders, cc, price(₹), lift(kg), wd, gearbox, ps, popular */
    private const TRACTORS = [
        ['Mahindra', '575 DI XP Plus', 47, 4, 2979, 730000, 1600, '2WD', '8 Forward + 2 Reverse', true, true],
        ['Mahindra', '265 DI Power Plus', 30, 3, 2048, 545000, 1200, '2WD', '8 Forward + 2 Reverse', false, true],
        ['Mahindra', 'Arjun Novo 605 DI-i', 57, 4, 3531, 935000, 1800, '2WD', '15 Forward + 3 Reverse', true, true],
        ['Mahindra', 'JIVO 245 DI 4WD', 24, 3, 1366, 480000, 750, '4WD', '9 Forward + 3 Reverse', true, false],
        ['Mahindra', '475 DI XP Plus', 44, 4, 2730, 690000, 1500, '2WD', '8 Forward + 2 Reverse', true, false],
        ['Mahindra', 'Yuvo Tech Plus 585', 49, 4, 2979, 785000, 1800, '2WD', '12 Forward + 3 Reverse', true, false],
        ['Swaraj', '744 FE', 48, 3, 3136, 710000, 1700, '2WD', '8 Forward + 2 Reverse', true, true],
        ['Swaraj', '735 FE', 40, 3, 2734, 620000, 1500, '2WD', '8 Forward + 2 Reverse', false, true],
        ['Swaraj', '963 FE', 60, 3, 3478, 985000, 2000, '2WD', '8 Forward + 2 Reverse', true, false],
        ['Swaraj', '855 FE', 52, 3, 3478, 830000, 2000, '2WD', '8 Forward + 2 Reverse', true, false],
        ['Swaraj', '724 XM Orchard', 25, 2, 1442, 465000, 780, '2WD', '6 Forward + 2 Reverse', false, false],
        ['Sonalika', 'DI 745 III Sikander', 50, 3, 3065, 705000, 2000, '2WD', '8 Forward + 2 Reverse', true, true],
        ['Sonalika', 'DI 35', 39, 3, 2780, 590000, 1200, '2WD', '8 Forward + 2 Reverse', false, true],
        ['Sonalika', 'DI 60 Sikander', 60, 4, 4088, 1010000, 2200, '2WD', '10 Forward + 2 Reverse', true, false],
        ['Sonalika', 'GT 20 RX', 20, 2, 1000, 385000, 600, '2WD', '6 Forward + 2 Reverse', false, false],
        ['John Deere', '5050 D', 50, 3, 2900, 895000, 1600, '2WD', '8 Forward + 4 Reverse', true, true],
        ['John Deere', '5310 4WD', 55, 3, 2900, 1240000, 2000, '4WD', '12 Forward + 4 Reverse', true, true],
        ['John Deere', '5105', 40, 3, 2900, 745000, 1600, '2WD', '8 Forward + 4 Reverse', true, false],
        ['Massey Ferguson', '241 DI Maha Shakti', 42, 3, 2700, 660000, 1600, '2WD', '8 Forward + 2 Reverse', true, true],
        ['Massey Ferguson', '1035 DI', 36, 3, 2400, 570000, 1100, '2WD', '6 Forward + 2 Reverse', false, false],
        ['Massey Ferguson', '9500 4WD', 58, 4, 4100, 1180000, 2000, '4WD', '12 Forward + 4 Reverse', true, false],
        ['New Holland', '3630 TX Plus', 55, 3, 2931, 985000, 1700, '2WD', '8 Forward + 2 Reverse', true, false],
        ['New Holland', '3230 TX', 42, 3, 2500, 720000, 1500, '2WD', '8 Forward + 2 Reverse', true, false],
        ['Eicher', '380 Super Plus', 40, 3, 2500, 615000, 1500, '2WD', '8 Forward + 2 Reverse', false, true],
        ['Eicher', '485 Super Plus', 45, 3, 2945, 690000, 1650, '2WD', '8 Forward + 2 Reverse', true, false],
        ['Eicher', '242', 25, 2, 1557, 430000, 750, '2WD', '6 Forward + 2 Reverse', false, false],
        ['Powertrac', 'Euro 439 Plus', 41, 3, 2340, 640000, 1500, '2WD', '8 Forward + 2 Reverse', true, true],
        ['Powertrac', '434 DS Super Saver', 37, 3, 2340, 590000, 1200, '2WD', '8 Forward + 2 Reverse', false, false],
        ['Farmtrac', '60 Powermaxx', 50, 3, 3514, 745000, 2000, '2WD', '8 Forward + 2 Reverse', true, true],
        ['Farmtrac', '45 Classic', 48, 3, 3120, 700000, 1800, '2WD', '8 Forward + 2 Reverse', true, false],
        ['Kubota', 'MU4501 4WD', 45, 4, 2434, 985000, 1640, '4WD', '8 Forward + 4 Reverse', true, false],
        ['Kubota', 'Neostar B2741 4WD', 27, 3, 1261, 660000, 750, '4WD', '9 Forward + 3 Reverse', true, false],
        ['Solis', '5015 E 4WD', 50, 3, 2911, 890000, 2000, '4WD', '10 Forward + 10 Reverse', true, false],
        ['Preet', '4549', 45, 3, 3066, 680000, 1800, '2WD', '8 Forward + 2 Reverse', true, false],
        ['Indo Farm', '2042 DI', 42, 3, 2500, 660000, 1600, '2WD', '8 Forward + 2 Reverse', true, false],
        ['ACE', 'DI 350 NG', 35, 3, 2354, 545000, 1100, '2WD', '8 Forward + 2 Reverse', false, false],
        ['Captain', '250 DI', 25, 2, 1500, 420000, 750, '2WD', '6 Forward + 2 Reverse', false, false],
        ['VST Tillers', 'MT 224 1D Samraat', 24, 3, 1318, 445000, 750, '4WD', '9 Forward + 3 Reverse', false, false],
        ['Force Motors', 'Balwan 550', 50, 4, 3120, 760000, 1800, '2WD', '8 Forward + 2 Reverse', true, false],
        ['Digitrac', 'PP 43i', 45, 3, 2340, 675000, 1600, '2WD', '8 Forward + 2 Reverse', true, false],
    ];

    /** brand, model, category, price, required hp min/max, working width mm, blades */
    private const IMPLEMENTS = [
        ['Shaktiman', 'Regular Rotary Tiller 7 Feet', 'Rotavator', 92000, 45, 60, 2100, 48],
        ['Shaktiman', 'Champion Rotary Tiller 5 Feet', 'Rotavator', 74000, 35, 45, 1500, 36],
        ['Fieldking', 'Rotary Tiller 6 Feet', 'Rotavator', 82000, 40, 50, 1800, 42],
        ['Maschio Gaspardo', 'Virat Plus Rotavator', 'Rotavator', 118000, 50, 65, 2050, 48],
        ['Fieldking', 'Cultivator 9 Tyne', 'Cultivator', 46500, 40, 55, 2000, 9],
        ['Landforce', 'Spring Loaded Cultivator 11 Tyne', 'Cultivator', 58000, 45, 60, 2400, 11],
        ['Fieldking', 'Mounted Disc Plough 3 Bottom', 'Plough', 68000, 45, 60, 1200, 3],
        ['Landforce', 'Reversible MB Plough 2 Bottom', 'Plough', 96000, 50, 65, 900, 2],
        ['Landforce', 'Seed Cum Fertilizer Drill 11 Row', 'Seed Drill', 78000, 35, 50, 2200, 11],
        ['Dasmesh', 'Multi Crop Thresher 522', 'Thresher', 215000, 45, 60, 1800, 0],
        ['Dasmesh', 'Straw Reaper 555', 'Straw Reaper', 385000, 50, 65, 2100, 0],
        ['Fieldking', 'Boom Sprayer 400 L', 'Sprayer', 52000, 30, 45, 6000, 0],
        ['Landforce', 'Laser Land Leveller', 'Laser Leveller', 245000, 50, 70, 2400, 0],
        ['Shaktiman', 'Round Baler', 'Baler', 620000, 55, 75, 1600, 0],
        ['Fieldking', 'Mulcher 6 Feet', 'Mulcher', 148000, 45, 60, 1800, 0],
        ['Landforce', 'Post Hole Digger 12 inch', 'Post Hole Digger', 38000, 35, 50, 300, 0],
        ['Fieldking', 'Ridger 3 Row', 'Ridger', 32000, 35, 45, 1500, 3],
        ['Shaktiman', 'Hydraulic Trailer 5 Ton', 'Trailer', 195000, 40, 60, 2100, 0],
    ];

    public function run(): void
    {
        $products = app(ProductService::class);
        $prices = app(PriceService::class);

        $brands = Brand::pluck('id', 'name');
        $attributes = SpecAttribute::pluck('id', 'slug');
        $tractorCategory = Category::where('slug', 'tractors')->firstOrFail();
        $states = State::whereIn('code', ['UP', 'MP', 'RJ', 'MH', 'PB', 'HR', 'GJ', 'BR'])->get();

        $created = 0;

        foreach (self::TRACTORS as $row) {
            [$brandName, $model, $hp, $cylinders, $cc, $price, $lift, $wd, $gearbox, $powerSteering, $popular] = $row;

            if (! isset($brands[$brandName])) {
                continue;
            }

            $product = Product::withTrashed()
                ->where('brand_id', $brands[$brandName])
                ->where('slug', str($model)->slug())
                ->first()
                ?? $products->create([
                    'brand_id' => $brands[$brandName],
                    'category_id' => $tractorCategory->id,
                    'name' => $model,
                    'status' => 'available',
                    'hp_min' => $hp,
                    'launch_year' => rand(2019, 2025),
                    'short_description' => "The {$brandName} {$model} is a {$hp} HP tractor with a {$cylinders}-cylinder engine and {$lift} kg lift capacity, suited to Indian field conditions.",
                    'description' => "The {$brandName} {$model} delivers {$hp} HP from a {$cc} cc {$cylinders}-cylinder engine. It carries a {$gearbox} gearbox and a hydraulic lift rated at {$lift} kg, making it a practical fit for ploughing, rotavation and haulage on small to mid-sized holdings.\n\nSample catalogue entry — verify all figures against the manufacturer before publishing.",
                    'is_popular' => $popular,
                    'is_active' => true,
                ]);

            $products->syncSpecs($product, array_filter([
                $attributes['engine-hp'] ?? null => $hp,
                $attributes['no-of-cylinders'] ?? null => $cylinders,
                $attributes['engine-capacity'] ?? null => $cc,
                $attributes['lifting-capacity'] ?? null => $lift,
                $attributes['wheel-drive'] ?? null => $wd,
                $attributes['gearbox'] ?? null => $gearbox,
                $attributes['power-steering'] ?? null => $powerSteering ? '1' : '0',
                $attributes['pto-hp'] ?? null => round($hp * 0.86, 1),
                $attributes['fuel-type'] ?? null => 'Diesel',
                $attributes['brake-type'] ?? null => 'Oil Immersed Brakes',
                $attributes['fuel-tank-capacity'] ?? null => $hp > 45 ? 60 : 45,
                $attributes['warranty'] ?? null => '2000 Hours / 2 Years',
                $attributes['ac-cabin'] ?? null => '0',
            ], fn ($k) => $k !== null, ARRAY_FILTER_USE_KEY));

            // National price, then state rows with realistic RTO and insurance loading.
            $prices->save($product, [
                'state_id' => null,
                'ex_showroom' => $price,
                'rto_charges' => round($price * 0.034, -2),
                'insurance_amount' => round($price * 0.025, -2),
            ]);

            foreach ($states as $state) {
                $variance = 1 + (crc32($state->code.$product->id) % 40 - 20) / 1000; // ±2%

                $prices->save($product, [
                    'state_id' => $state->id,
                    'ex_showroom' => round($price * $variance, -2),
                    'rto_charges' => round($price * (0.028 + (crc32($state->code) % 15) / 1000), -2),
                    'insurance_amount' => round($price * 0.025, -2),
                ]);
            }

            $product->forceFill(['popularity_score' => $popular ? rand(700, 1000) : rand(100, 690)])->saveQuietly();
            $created++;
        }

        foreach (self::IMPLEMENTS as [$brandName, $model, $categoryName, $price, $hpMin, $hpMax, $width, $blades]) {
            $category = Category::where('name', $categoryName)->first();

            if (! isset($brands[$brandName]) || ! $category) {
                continue;
            }

            $product = Product::withTrashed()
                ->where('brand_id', $brands[$brandName])
                ->where('slug', str($model)->slug())
                ->first()
                ?? $products->create([
                    'brand_id' => $brands[$brandName],
                    'category_id' => $category->id,
                    'name' => $model,
                    'status' => 'available',
                    'short_description' => "{$brandName} {$model} — a {$categoryName} for tractors from {$hpMin} to {$hpMax} HP.",
                    'is_active' => true,
                ]);

            $products->syncSpecs($product, array_filter([
                $attributes['required-hp-min'] ?? null => $hpMin,
                $attributes['required-hp-max'] ?? null => $hpMax,
                $attributes['working-width'] ?? null => $width,
                $attributes['no-of-blades'] ?? null => $blades ?: null,
                $attributes['hitch-type'] ?? null => 'Category II',
            ], fn ($k) => $k !== null, ARRAY_FILTER_USE_KEY));

            $prices->save($product, [
                'state_id' => null,
                'ex_showroom' => $price,
                'rto_charges' => 0,
                'insurance_amount' => 0,
            ]);

            $product->forceFill(['popularity_score' => rand(100, 600)])->saveQuietly();
            $created++;
        }

        $this->command?->info("Demo catalogue: {$created} products with specs and prices");
        $this->command?->warn('Sample figures — replace with manufacturer data before launch.');
    }
}
