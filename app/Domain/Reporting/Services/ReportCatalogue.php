<?php

namespace App\Domain\Reporting\Services;

use App\Models\Dealer;
use App\Models\InsuranceEnquiry;
use App\Models\Lead;
use App\Models\LoanApplication;
use App\Models\Product;
use App\Models\UsedListing;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Every report the admin panel offers.
 *
 * Each entry names the permission that guards it and whether it contains
 * contact numbers, because an export leaves the building — a role that sees
 * masked numbers on screen must not be able to download the real ones.
 */
class ReportCatalogue
{
    public function __construct(private readonly Request $request) {}

    /** @return array<string, array{title: string, permission: string, description: string}> */
    public static function index(): array
    {
        return [
            'leads' => [
                'title' => 'Leads',
                'permission' => 'leads.export',
                'description' => 'Every enquiry with its type, district, status and source.',
            ],
            'customers' => [
                'title' => 'Customers',
                'permission' => 'users.export',
                'description' => 'Registered buyers and sellers, with what each has listed and enquired.',
            ],
            'tractors' => [
                'title' => 'Tractors & machinery',
                'permission' => 'products.view',
                'description' => 'The catalogue with HP, price band, rating and view count.',
            ],
            'used-listings' => [
                'title' => 'Used listings',
                'permission' => 'listings.export',
                'description' => 'Machines for sale, their asking price, status and interest.',
            ],
            'dealers' => [
                'title' => 'Dealers',
                'permission' => 'dealers.export',
                'description' => 'The network with plan, district, rating and lead volume.',
            ],
            'loans' => [
                'title' => 'Loan applications',
                'permission' => 'loans.export',
                'description' => 'Applications with amount, EMI, lender stage and status.',
            ],
            'insurance' => [
                'title' => 'Insurance enquiries',
                'permission' => 'insurance.view',
                'description' => 'Cover requested, machine details and follow-up status.',
            ],
        ];
    }

    public function make(string $key): ReportDefinition
    {
        $from = $this->request->query('from')
            ? today()->parse($this->request->query('from'))->startOfDay()
            : today()->subDays(90)->startOfDay();

        $to = $this->request->query('to')
            ? today()->parse($this->request->query('to'))->endOfDay()
            : today()->endOfDay();

        $window = __('Created :from to :to', [
            'from' => $from->format('d M Y'), 'to' => $to->format('d M Y'),
        ]);

        // Contact numbers leave the building in an export exactly as they appear
        // on screen: full only for a role that holds leads.view_contact.
        $contact = $this->request->user()?->can('leads.view_contact') ?? false;
        $number = fn (?object $model, string $field = 'mobile') => $model === null
            ? null
            : ($contact ? $model->{$field} : ($model->masked_mobile ?? null));

        return match ($key) {
            'leads' => new ReportDefinition(
                key: 'leads',
                title: __('Leads'),
                subtitle: $window,
                orientation: 'landscape',
                query: fn () => Lead::with(['district', 'state', 'source', 'assignments.dealer'])
                    ->whereBetween('created_at', [$from, $to])->orderBy('id'),
                columns: [
                    __('Reference') => fn (Lead $l) => $l->reference_no,
                    __('Date') => fn (Lead $l) => $l->created_at?->format('d M Y'),
                    __('Type') => fn (Lead $l) => str_replace('_', ' ', $l->type),
                    __('Name') => fn (Lead $l) => $l->name,
                    __('Mobile') => fn (Lead $l) => $number($l),
                    __('District') => fn (Lead $l) => $l->district?->name,
                    __('State') => fn (Lead $l) => $l->state?->name,
                    __('Interested in') => fn (Lead $l) => $l->leadable?->title
                        ?? $l->leadable?->name ?? $l->leadable?->display_name,
                    __('Assigned to') => fn (Lead $l) => $l->assignments->first()?->dealer?->display_name,
                    __('Status') => fn (Lead $l) => $l->status,
                    __('Source') => fn (Lead $l) => $l->source?->name ?? $l->channel,
                ],
            ),

            'customers' => new ReportDefinition(
                key: 'customers',
                title: __('Customers'),
                subtitle: $window,
                query: fn () => User::withCount(['usedListings', 'leads'])
                    ->where('user_type', 'customer')
                    ->whereBetween('created_at', [$from, $to])->orderBy('id'),
                numeric: [__('Listings'), __('Enquiries')],
                columns: [
                    __('Name') => fn (User $u) => $u->name,
                    __('Mobile') => fn (User $u) => $number($u),
                    __('Email') => fn (User $u) => $u->email,
                    __('District') => fn (User $u) => $u->district?->name,
                    __('Listings') => fn (User $u) => $u->used_listings_count,
                    __('Enquiries') => fn (User $u) => $u->leads_count,
                    __('Joined') => fn (User $u) => $u->created_at?->format('d M Y'),
                    __('Active') => fn (User $u) => $u->is_active ? __('yes') : __('no'),
                ],
            ),

            'tractors' => new ReportDefinition(
                key: 'tractors',
                title: __('Tractors & machinery'),
                subtitle: __('Full catalogue'),
                orientation: 'landscape',
                query: fn () => Product::with(['brand', 'category'])->orderBy('brand_id')->orderBy('name'),
                numeric: [__('HP'), __('Price from'), __('Price to'), __('Rating'), __('Views')],
                columns: [
                    __('Brand') => fn (Product $p) => $p->brand?->name,
                    __('Model') => fn (Product $p) => $p->name,
                    __('Category') => fn (Product $p) => $p->category?->name,
                    __('HP') => fn (Product $p) => $p->hp_min ? (int) $p->hp_min : null,
                    __('Price from') => fn (Product $p) => $p->price_min ? (float) $p->price_min : null,
                    __('Price to') => fn (Product $p) => $p->price_max ? (float) $p->price_max : null,
                    __('Rating') => fn (Product $p) => $p->rating_count ? (float) $p->rating_avg : null,
                    __('Views') => fn (Product $p) => $p->view_count,
                    __('Status') => fn (Product $p) => $p->status,
                ],
            ),

            'used-listings' => new ReportDefinition(
                key: 'used-listings',
                title: __('Used listings'),
                subtitle: $window,
                orientation: 'landscape',
                query: fn () => UsedListing::with(['brand', 'city', 'state', 'seller'])
                    ->whereBetween('created_at', [$from, $to])->orderBy('id'),
                numeric: [__('Year'), __('Hours'), __('Asking price'), __('Views'), __('Enquiries')],
                columns: [
                    __('Reference') => fn (UsedListing $l) => $l->reference_no,
                    __('Machine') => fn (UsedListing $l) => $l->title,
                    __('Brand') => fn (UsedListing $l) => $l->brand?->name,
                    __('Year') => fn (UsedListing $l) => $l->manufacturing_year,
                    __('Hours') => fn (UsedListing $l) => $l->engine_hours,
                    __('Asking price') => fn (UsedListing $l) => (float) $l->expected_price,
                    __('City') => fn (UsedListing $l) => $l->city?->name,
                    __('Seller') => fn (UsedListing $l) => $l->seller?->name,
                    __('Mobile') => fn (UsedListing $l) => $number($l->seller),
                    __('Views') => fn (UsedListing $l) => $l->view_count,
                    __('Enquiries') => fn (UsedListing $l) => $l->lead_count,
                    __('Status') => fn (UsedListing $l) => $l->status,
                ],
            ),

            'dealers' => new ReportDefinition(
                key: 'dealers',
                title: __('Dealers'),
                subtitle: __('Full network'),
                orientation: 'landscape',
                query: fn () => Dealer::with(['city', 'district', 'state', 'activeSubscription.plan'])
                    ->withCount('leadAssignments')->orderBy('id'),
                numeric: [__('Rating'), __('Leads received'), __('Response score')],
                columns: [
                    __('Code') => fn (Dealer $d) => $d->code,
                    __('Dealership') => fn (Dealer $d) => $d->display_name,
                    __('Type') => fn (Dealer $d) => str_replace('_', ' ', $d->dealer_type),
                    __('Mobile') => fn (Dealer $d) => $number($d),
                    __('District') => fn (Dealer $d) => $d->district?->name,
                    __('State') => fn (Dealer $d) => $d->state?->name,
                    __('Plan') => fn (Dealer $d) => $d->activeSubscription?->plan?->name,
                    __('Rating') => fn (Dealer $d) => $d->rating_count ? (float) $d->rating_avg : null,
                    __('Leads received') => fn (Dealer $d) => $d->lead_assignments_count,
                    __('Response score') => fn (Dealer $d) => (float) $d->response_score,
                    __('Status') => fn (Dealer $d) => $d->verification_status,
                ],
            ),

            'loans' => new ReportDefinition(
                key: 'loans',
                title: __('Loan applications'),
                subtitle: $window,
                orientation: 'landscape',
                query: fn () => LoanApplication::with(['district', 'state', 'lenders'])
                    ->whereBetween('created_at', [$from, $to])->orderBy('id'),
                numeric: [__('Machine price'), __('Loan amount'), __('EMI'), __('Tenure')],
                columns: [
                    __('Reference') => fn (LoanApplication $a) => $a->reference_no,
                    __('Date') => fn (LoanApplication $a) => $a->created_at?->format('d M Y'),
                    __('Applicant') => fn (LoanApplication $a) => $a->applicant_name,
                    __('Mobile') => fn (LoanApplication $a) => $number($a),
                    __('District') => fn (LoanApplication $a) => $a->district?->name,
                    __('Machine price') => fn (LoanApplication $a) => (float) $a->machinery_price,
                    __('Loan amount') => fn (LoanApplication $a) => (float) $a->loan_amount,
                    __('EMI') => fn (LoanApplication $a) => (float) $a->calculated_emi,
                    __('Tenure') => fn (LoanApplication $a) => $a->tenure_months,
                    __('Lenders') => fn (LoanApplication $a) => $a->lenders->pluck('name')->implode(', '),
                    __('Status') => fn (LoanApplication $a) => str_replace('_', ' ', $a->status),
                ],
            ),

            'insurance' => new ReportDefinition(
                key: 'insurance',
                title: __('Insurance enquiries'),
                subtitle: $window,
                orientation: 'landscape',
                query: fn () => InsuranceEnquiry::with(['partner', 'product.brand', 'assignee'])
                    ->whereBetween('created_at', [$from, $to])->orderBy('id'),
                numeric: [__('Year'), __('Expected IDV')],
                columns: [
                    __('Reference') => fn (InsuranceEnquiry $e) => $e->reference_no,
                    __('Date') => fn (InsuranceEnquiry $e) => $e->created_at?->format('d M Y'),
                    __('Applicant') => fn (InsuranceEnquiry $e) => $e->applicant_name,
                    __('Mobile') => fn (InsuranceEnquiry $e) => $number($e),
                    __('Cover') => fn (InsuranceEnquiry $e) => str_replace('_', ' ', $e->coverage_type),
                    __('Machine') => fn (InsuranceEnquiry $e) => $e->product
                        ? trim($e->product->brand?->name.' '.$e->product->name)
                        : null,
                    __('Registration') => fn (InsuranceEnquiry $e) => $e->registration_number,
                    __('Year') => fn (InsuranceEnquiry $e) => $e->manufacturing_year,
                    __('Expected IDV') => fn (InsuranceEnquiry $e) => $e->idv_expected ? (float) $e->idv_expected : null,
                    __('Insurer') => fn (InsuranceEnquiry $e) => $e->partner?->name,
                    __('Policy ends') => fn (InsuranceEnquiry $e) => $e->previous_policy_expiry?->format('d M Y'),
                    __('Status') => fn (InsuranceEnquiry $e) => $e->status,
                ],
            ),

            default => abort(404),
        };
    }
}
