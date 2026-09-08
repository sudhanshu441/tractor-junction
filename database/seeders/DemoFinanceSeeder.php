<?php

namespace Database\Seeders;

use App\Domain\Finance\Services\InsuranceService;
use App\Domain\Finance\Services\LoanApplicationService;
use App\Domain\Marketplace\Services\InspectionService;
use App\Models\InspectionChecklistItem;
use App\Models\LoanApplication;
use App\Models\Product;
use App\Models\UsedListing;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo loan applications, an inspection and an insurance enquiry, so the
 * finance desk, the inspection queue and the insurance inbox all have work in
 * them on a fresh install. Sample data — delete before launch.
 */
class DemoFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $loans = app(LoanApplicationService::class);
        $inspections = app(InspectionService::class);
        $insurance = app(InsuranceService::class);

        $applicants = User::where('user_type', 'customer')->limit(3)->get();
        $products = Product::with('brand')->whereHas('category', fn ($q) => $q->where('type', 'tractor'))
            ->limit(3)->get();

        if ($applicants->isEmpty() || $products->isEmpty()) {
            $this->command?->warn('Skipping demo finance — users or products missing.');

            return;
        }

        $rows = [
            ['new_purchase', 850000, 200000, 60, 11.5, 420000, 'farming', 'submitted'],
            ['new_purchase', 640000, 100000, 48, 12.5, 300000, 'farming', 'docs_pending'],
            ['used_purchase', 380000, 80000, 36, 14.0, 260000, 'business', 'under_review'],
        ];

        foreach ($rows as $i => [$purpose, $price, $down, $tenure, $rate, $income, $source, $target]) {
            $applicant = $applicants[$i % $applicants->count()];

            if (LoanApplication::where('user_id', $applicant->id)->where('machinery_price', $price)->exists()) {
                continue;
            }

            $application = $loans->saveDraft($loans->maskIdentifiers([
                'purpose' => $purpose,
                'applicant_name' => $applicant->name,
                'mobile' => $applicant->mobile,
                'date_of_birth' => now()->subYears(38 + $i)->toDateString(),
                'pan' => 'ABCDE'.(1234 + $i).'F',
                'aadhaar' => '9876543210'.(10 + $i),
                'annual_income' => $income,
                'income_source' => $source,
                'land_holding_acres' => 4 + $i,
                'machinery_price' => $price,
                'down_payment' => $down,
                'tenure_months' => $tenure,
                'expected_interest_rate' => $rate,
                'state_id' => $applicant->state_id,
                'district_id' => $applicant->district_id,
            ]), null, $applicant->id, $products[$i % $products->count()]);

            $loans->submit($application);

            if ($target !== 'submitted') {
                $loans->changeStatus($application->refresh(), 'under_review', __('Picked up by the finance desk.'));
            }

            if ($target === 'docs_pending') {
                $loans->changeStatus($application->refresh(), 'docs_pending', __('Land record and bank statement outstanding.'));
            }
        }

        $this->seedInspection($inspections);

        $insurance->capture([
            'name' => $applicants->first()->name,
            'mobile' => $applicants->first()->mobile,
            'coverage_type' => 'comprehensive',
            'manufacturing_year' => now()->subYears(4)->year,
            'registration_number' => 'UP34 AB 4512',
            'product_id' => $products->first()->id,
            'state_id' => $applicants->first()->state_id,
        ], $applicants->first());

        $this->command?->info('  Demo finance: '.LoanApplication::count().' loan applications');
    }

    /**
     * One listing goes all the way through inspection so the verified badge and
     * the grade are visible on the public site without anyone scoring by hand.
     */
    private function seedInspection(InspectionService $inspections): void
    {
        $listing = UsedListing::where('status', 'live')->whereDoesntHave('inspection')->first();
        $inspector = User::whereHas('roles', fn ($q) => $q->where('name', 'super-admin'))->first();

        if (! $listing || ! $inspector) {
            return;
        }

        $inspection = $inspections->request($listing);
        $inspections->schedule($inspection, $inspector->id, now()->subDays(2)->toDateTimeString());
        $inspections->changeStatus($inspection->refresh(), 'in_progress');

        $scores = InspectionChecklistItem::where('is_active', true)->get()
            ->mapWithKeys(fn ($item) => [$item->id => ['score' => random_int(7, 10)]])
            ->all();

        $inspections->complete($inspection->refresh(), $scores, __('Machine is in working order; tyres have roughly one season left.'));

        // Left unapproved on purpose: the approval queue should not start empty.
    }
}
