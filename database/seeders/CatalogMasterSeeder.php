<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\SearchSynonym;
use App\Models\SpecAttribute;
use App\Models\SpecGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Brands, categories and the specification schema. These are masters, not demo
 * data — they are what the catalogue team works against on day one.
 */
class CatalogMasterSeeder extends Seeder
{
    private const BRANDS = [
        ['Mahindra', 'India', 1945, true],
        ['Swaraj', 'India', 1974, true],
        ['Sonalika', 'India', 1969, true],
        ['John Deere', 'United States', 1837, true],
        ['Massey Ferguson', 'United States', 1953, true],
        ['New Holland', 'Italy', 1895, true],
        ['Eicher', 'India', 1948, true],
        ['Powertrac', 'India', 1970, true],
        ['Farmtrac', 'India', 1996, true],
        ['Kubota', 'Japan', 1890, true],
        ['VST Tillers', 'India', 1967, false],
        ['Force Motors', 'India', 1958, false],
        ['Preet', 'India', 1980, false],
        ['Indo Farm', 'India', 1994, false],
        ['ACE', 'India', 1995, false],
        ['Captain', 'India', 1994, false],
        ['Solis', 'India', 2015, false],
        ['Standard', 'India', 1993, false],
        ['Trakstar', 'India', 2018, false],
        ['Digitrac', 'India', 2019, false],
        ['Shaktiman', 'India', 1994, false],
        ['Fieldking', 'India', 1974, false],
        ['Landforce', 'India', 2015, false],
        ['Dasmesh', 'India', 1985, false],
        ['Maschio Gaspardo', 'Italy', 1964, false],
    ];

    private const CATEGORIES = [
        // type, name, children
        ['tractor', 'Tractors', ['Mini Tractors', '4WD Tractors', 'AC Cabin Tractors', 'Orchard Tractors', 'Utility Tractors']],
        ['implement', 'Implements', ['Rotavator', 'Cultivator', 'Plough', 'Seed Drill', 'Thresher', 'Sprayer', 'Trailer', 'Baler', 'Laser Leveller', 'Mulcher', 'Post Hole Digger', 'Ridger']],
        ['harvester', 'Harvesters', ['Combine Harvester', 'Straw Reaper', 'Forage Harvester']],
        ['tyre', 'Tractor Tyres', ['Front Tyres', 'Rear Tyres']],
        ['farm_tool', 'Farm Tools', ['Water Pumps', 'Sprayers', 'Chaff Cutters']],
    ];

    /** group => [name, data_type, unit, filterable, key_spec] */
    private const SPECS = [
        'Engine' => [
            ['Engine HP', 'decimal', 'HP', true, true],
            ['No. of Cylinders', 'int', null, true, true],
            ['Engine Capacity', 'decimal', 'cc', false, true],
            ['Rated RPM', 'int', 'rpm', false, false],
            ['Fuel Type', 'string', null, true, false],
            ['Air Filter', 'string', null, false, false],
            ['Cooling System', 'string', null, false, false],
            ['Torque', 'decimal', 'Nm', false, false],
        ],
        'Transmission' => [
            ['Transmission Type', 'string', null, true, false],
            ['Clutch', 'string', null, false, false],
            ['Gearbox', 'string', null, false, true],
            ['Forward Speed', 'decimal', 'kmph', false, false],
            ['Reverse Speed', 'decimal', 'kmph', false, false],
        ],
        'Power Take-off' => [
            ['PTO HP', 'decimal', 'HP', false, true],
            ['PTO Type', 'string', null, false, false],
            ['PTO RPM', 'int', 'rpm', false, false],
        ],
        'Hydraulics' => [
            ['Lifting Capacity', 'decimal', 'kg', true, true],
            ['3 Point Linkage', 'string', null, false, false],
            ['Hydraulic Control', 'string', null, false, false],
        ],
        'Brakes & Steering' => [
            ['Brake Type', 'string', null, false, true],
            ['Steering Type', 'string', null, false, false],
            ['Power Steering', 'boolean', null, true, false],
        ],
        'Wheels & Tyres' => [
            ['Wheel Drive', 'string', null, true, true],
            ['Front Tyre Size', 'string', null, false, false],
            ['Rear Tyre Size', 'string', null, false, false],
        ],
        'Dimensions & Weight' => [
            ['Total Weight', 'decimal', 'kg', false, false],
            ['Wheelbase', 'decimal', 'mm', false, false],
            ['Overall Length', 'decimal', 'mm', false, false],
            ['Ground Clearance', 'decimal', 'mm', false, false],
            ['Fuel Tank Capacity', 'decimal', 'L', false, true],
        ],
        'Comfort & Other' => [
            ['AC Cabin', 'boolean', null, true, false],
            ['Seat Type', 'string', null, false, false],
            ['Warranty', 'string', null, false, true],
            ['Accessories', 'string', null, false, false],
        ],
        'Implement Fitment' => [
            ['Required HP Min', 'decimal', 'HP', true, true],
            ['Required HP Max', 'decimal', 'HP', false, false],
            ['Working Width', 'decimal', 'mm', false, true],
            ['No. of Blades', 'int', null, false, true],
            ['Hitch Type', 'string', null, true, false],
        ],
    ];

    public function run(): void
    {
        foreach (self::BRANDS as $i => [$name, $country, $year, $popular]) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'country' => $country,
                    'founded_year' => $year,
                    'is_popular' => $popular,
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }

        $categoryIds = [];

        foreach (self::CATEGORIES as $ci => [$type, $parentName, $children]) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($parentName)],
                ['type' => $type, 'name' => $parentName, 'sort_order' => $ci, 'is_active' => true],
            );

            $categoryIds[$type][] = $parent->id;

            foreach ($children as $i => $childName) {
                $child = Category::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'type' => $type,
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'sort_order' => $i,
                        'is_active' => true,
                    ],
                );

                $categoryIds[$type][] = $child->id;
            }
        }

        $tractorAttributeIds = [];
        $implementAttributeIds = [];
        $groupIndex = 0;

        foreach (self::SPECS as $groupName => $attributes) {
            $group = SpecGroup::updateOrCreate(
                ['slug' => Str::slug($groupName)],
                ['name' => $groupName, 'sort_order' => $groupIndex++, 'is_active' => true],
            );

            foreach ($attributes as $i => [$name, $type, $unit, $filterable, $keySpec]) {
                $attribute = SpecAttribute::updateOrCreate(
                    ['slug' => Str::slug($name)],
                    [
                        'spec_group_id' => $group->id,
                        'name' => $name,
                        'data_type' => $type,
                        'unit' => $unit,
                        'is_filterable' => $filterable,
                        'is_comparable' => true,
                        'is_key_spec' => $keySpec,
                        'sort_order' => $i,
                    ],
                );

                if ($groupName === 'Implement Fitment') {
                    $implementAttributeIds[] = $attribute->id;
                } else {
                    $tractorAttributeIds[] = $attribute->id;
                }
            }
        }

        // Map the schema onto categories, so product forms build themselves.
        foreach ($categoryIds['tractor'] ?? [] as $id) {
            Category::find($id)?->specAttributes()->sync($this->pivot($tractorAttributeIds));
        }

        foreach (array_merge($categoryIds['implement'] ?? [], $categoryIds['harvester'] ?? []) as $id) {
            Category::find($id)?->specAttributes()->sync($this->pivot($implementAttributeIds));
        }

        $this->seedSynonyms();

        $this->command?->info('Catalogue masters: '.Brand::count().' brands · '
            .Category::count().' categories · '.SpecAttribute::count().' specifications');
    }

    private function pivot(array $ids): array
    {
        return collect($ids)->mapWithKeys(fn ($id, $i) => [$id => ['sort_order' => $i, 'is_required' => false]])->all();
    }

    /** Hindi and common misspellings, so rural search terms reach the right models. */
    private function seedSynonyms(): void
    {
        $synonyms = [
            'महिंद्रा' => 'mahindra', 'mhindra' => 'mahindra', 'mahindr' => 'mahindra',
            'स्वराज' => 'swaraj', 'swaraaj' => 'swaraj',
            'सोनालिका' => 'sonalika', 'sonalka' => 'sonalika',
            'जॉन' => 'john', 'जॉनडियर' => 'john deere', 'jhon' => 'john',
            'आयशर' => 'eicher', 'icher' => 'eicher',
            'ट्रैक्टर' => 'tractor', 'tracter' => 'tractor', 'traktor' => 'tractor',
            'रोटावेटर' => 'rotavator', 'rotavater' => 'rotavator', 'rotovator' => 'rotavator',
            'कल्टीवेटर' => 'cultivator', 'हार्वेस्टर' => 'harvester',
            'थ्रेशर' => 'thresher', 'ट्रॉली' => 'trailer',
        ];

        foreach ($synonyms as $term => $canonical) {
            SearchSynonym::updateOrCreate(
                ['term' => $term],
                ['canonical' => $canonical, 'is_active' => true],
            );
        }
    }
}
