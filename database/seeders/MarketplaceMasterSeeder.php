<?php

namespace Database\Seeders;

use App\Models\InspectionChecklistItem;
use App\Models\LeadSource;
use App\Models\Plan;
use App\Models\RoutingRule;
use App\Models\ValuationRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Masters the marketplace and lead engine need before they can run:
 * lead sources, routing rules, valuation multipliers, inspection checklist, plans.
 */
class MarketplaceMasterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['Organic search', 'organic', null],
            ['Direct', 'direct', null],
            ['Google Ads', 'google-ads', 'google'],
            ['Facebook', 'facebook', 'facebook'],
            ['WhatsApp', 'whatsapp', 'whatsapp'],
            ['Referral', 'referral', 'referral'],
            ['Mobile app', 'app', 'app'],
        ] as $i => [$name, $slug, $utm]) {
            LeadSource::updateOrCreate(['slug' => $slug],
                ['name' => $name, 'utm_source' => $utm, 'is_active' => true]);
        }

        foreach ([
            ['Free', 0, 0, 2, 5],
            ['Silver', 2499, 60, 6, 25],
            ['Gold', 5999, 150, 12, 100],
        ] as $i => [$name, $price, $leadLimit, $dailyCap, $inventory]) {
            Plan::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'audience' => 'dealer',
                'price' => $price,
                'billing_cycle' => 'monthly',
                'lead_limit' => $leadLimit,
                'daily_lead_cap' => $dailyCap,
                'inventory_limit' => $inventory,
                'featured_listing_count' => $i * 2,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        // Ordered rules; the engine takes the first match. A used-listing lead is
        // handled in code (it belongs to the seller), so no rule is needed for it.
        RoutingRule::updateOrCreate(['name' => 'New machinery enquiries to verified dealers'], [
            'priority' => 10,
            'lead_type' => 'new_product',
            'assignee_type' => 'dealer',
            'daily_cap' => 0,           // 0 = use the dealer's plan cap
            'response_sla_minutes' => (int) config('kj.leads.response_sla_minutes'),
            'is_active' => true,
        ]);

        RoutingRule::updateOrCreate(['name' => 'Dealer enquiries to that dealer'], [
            'priority' => 20,
            'lead_type' => 'dealer',
            'assignee_type' => 'dealer',
            'response_sla_minutes' => 120,
            'is_active' => true,
        ]);

        RoutingRule::updateOrCreate(['name' => 'Finance and insurance to staff'], [
            'priority' => 30,
            'lead_type' => 'loan',
            'assignee_type' => 'staff',
            'response_sla_minutes' => 240,
            'is_active' => true,
        ]);

        RoutingRule::updateOrCreate(['name' => 'Everything else to staff'], [
            'priority' => 99,
            'lead_type' => null,
            'assignee_type' => 'staff',
            'response_sla_minutes' => 480,
            'is_active' => true,
        ]);

        $this->seedValuationRules();
        $this->seedChecklist();

        $this->command?->info('Marketplace masters: '.LeadSource::count().' sources · '
            .RoutingRule::count().' routing rules · '.ValuationRule::count().' valuation rules · '
            .InspectionChecklistItem::count().' checklist items');
    }

    /**
     * Depreciation curve for Indian farm machinery: steep for three years, then
     * flattening. Operations tune these rows rather than the code.
     */
    private function seedValuationRules(): void
    {
        $age = [
            'year_0' => 0.92, 'year_1' => 0.85, 'year_2' => 0.78, 'year_3' => 0.71,
            'year_4' => 0.65, 'year_5' => 0.59, 'year_6' => 0.53, 'year_7' => 0.48,
            'year_8' => 0.43, 'year_9' => 0.38, 'year_10' => 0.34, 'year_11' => 0.32,
            'year_12' => 0.31, 'year_13' => 0.30, 'year_14' => 0.29, 'year_15' => 0.28,
        ];

        $hours = [
            '0-1000' => 1.05, '1000-2000' => 1.00, '2000-3500' => 0.94,
            '3500-5000' => 0.87, '5000-plus' => 0.78, 'unknown' => 0.95,
        ];

        $condition = [
            'excellent' => 1.08, 'good' => 1.00, 'average' => 0.90, 'needs_repair' => 0.75,
        ];

        // Resale runs a little stronger where mechanisation is densest.
        $region = ['PB' => 1.04, 'HR' => 1.03, 'UP' => 1.00, 'MP' => 0.99,
            'RJ' => 0.98, 'MH' => 1.01, 'GJ' => 1.00, 'BR' => 0.96];

        foreach (['age' => $age, 'hours' => $hours, 'condition' => $condition, 'region' => $region] as $type => $rows) {
            foreach ($rows as $key => $multiplier) {
                ValuationRule::updateOrCreate(
                    ['factor_type' => $type, 'key' => $key, 'category_id' => null],
                    ['multiplier' => $multiplier, 'is_active' => true],
                );
            }
        }
    }

    private function seedChecklist(): void
    {
        $sections = [
            'Engine' => ['Cold start', 'Smoke colour', 'Oil leakage', 'Engine noise', 'Coolant condition'],
            'Transmission' => ['Gear shifting', 'Clutch play', 'Differential lock', 'Oil leakage'],
            'Hydraulics' => ['Lift response', 'Holding under load', 'Hose condition'],
            'Tyres' => ['Front tread', 'Rear tread', 'Rim condition'],
            'Body' => ['Sheet metal', 'Paint condition', 'Seat and controls'],
            'Electricals' => ['Battery', 'Lights', 'Self-starter', 'Meter working'],
            'Documents' => ['RC available', 'Insurance valid', 'Chassis number match'],
        ];

        $order = 0;

        foreach ($sections as $section => $items) {
            foreach ($items as $item) {
                InspectionChecklistItem::updateOrCreate(
                    ['section' => $section, 'name' => $item],
                    ['weight' => 1, 'sort_order' => $order++, 'is_active' => true],
                );
            }
        }
    }
}
