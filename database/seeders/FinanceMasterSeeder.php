<?php

namespace Database\Seeders;

use App\Models\InsurancePartner;
use App\Models\Lender;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The finance and insurance panel, plus the seller-facing promotion packages.
 *
 * The lender rows carry real-world shaped criteria (amount band, tenure band,
 * LTV) because the matcher only ever offers an applicant lenders whose
 * published criteria they actually meet — flat placeholder rows would make
 * that logic untestable.
 */
class FinanceMasterSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['State Bank of India', 'bank', 9.5, 12.5, 12, 84, 100000, 2500000, 1.0, 85],
            ['Punjab National Bank', 'bank', 9.8, 13.0, 12, 84, 100000, 2000000, 1.0, 80],
            ['HDFC Bank', 'bank', 10.5, 15.0, 12, 72, 150000, 3000000, 1.5, 80],
            ['Mahindra Finance', 'nbfc', 12.0, 18.0, 12, 60, 75000, 1500000, 2.0, 90],
            ['L&T Finance', 'nbfc', 11.5, 17.5, 12, 60, 100000, 1800000, 2.0, 85],
            ['District Co-operative Bank', 'coop', 8.5, 11.0, 12, 60, 50000, 800000, 0.5, 75],
        ] as $i => [$name, $type, $min, $max, $tenureMin, $tenureMax, $amountMin, $amountMax, $fee, $ltv]) {
            Lender::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'lender_type' => $type,
                'interest_min' => $min,
                'interest_max' => $max,
                'tenure_min_months' => $tenureMin,
                'tenure_max_months' => $tenureMax,
                'amount_min' => $amountMin,
                'amount_max' => $amountMax,
                'processing_fee_percent' => $fee,
                'max_ltv_percent' => $ltv,
                'states_served' => null,      // null = lends nationally
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        foreach ([
            ['New India Assurance', ['comprehensive', 'third_party', 'own_damage'],
                'Public sector insurer with the widest rural branch network for claim survey.'],
            ['United India Insurance', ['comprehensive', 'third_party'],
                'Cover for tractor plus trailer and implements under one policy.'],
            ['ICICI Lombard', ['comprehensive', 'own_damage'],
                'Same-day digital policy issue and cashless repair at empanelled workshops.'],
            ['Bajaj Allianz', ['comprehensive', 'third_party', 'own_damage'],
                'Add-ons for driver cover, PA cover and consumables.'],
        ] as $i => [$name, $coverage, $description]) {
            InsurancePartner::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'coverage_types' => $coverage,
                'description' => $description,
                'states_served' => null,      // null = writes nationally
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        // Promotion packages a private seller buys for one listing.
        foreach ([
            ['Spotlight 7 days', 299, ['Top of matching search results for 7 days', 'Highlighted card']],
            ['Spotlight 30 days', 899, ['Top of matching search results for 30 days', 'Highlighted card', 'Shown on the district landing page']],
        ] as $i => [$name, $price, $features]) {
            Plan::updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'audience' => 'seller',
                'price' => $price,
                'billing_cycle' => $i === 0 ? 'one_time' : 'monthly',
                'lead_limit' => 0,
                'daily_lead_cap' => 0,
                'inventory_limit' => 0,
                'featured_listing_count' => 1,
                'features' => $features,
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        $this->command?->info('  Finance masters: '.Lender::count().' lenders · '
            .InsurancePartner::count().' insurers · '
            .Plan::where('audience', 'seller')->count().' promotion packages');
    }
}
