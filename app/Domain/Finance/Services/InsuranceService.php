<?php

namespace App\Domain\Finance\Services;

use App\Domain\Lead\Services\LeadService;
use App\Models\InsuranceEnquiry;
use App\Models\InsurancePartner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Tractor insurance enquiries.
 *
 * The enquiry row is the insurance desk's working record; the lead it opens is
 * what the routing engine and the SLA reports see. One submission writes both,
 * so an enquiry can never sit outside the follow-up queue.
 */
class InsuranceService
{
    public const COVERAGE_TYPES = [
        'comprehensive' => 'Comprehensive — own damage and third party',
        'third_party' => 'Third party only',
        'own_damage' => 'Own damage only',
    ];

    public function __construct(private readonly LeadService $leads) {}

    public function nextReference(): string
    {
        $last = InsuranceEnquiry::max('id') ?? 0;

        return 'KJ-N-'.str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Partners that publish cover in this state. A partner with no state list
     * serves the whole country.
     *
     * Returned as plain arrays — this list is cached, and a serialised
     * Eloquent collection comes back broken on a warm cache.
     *
     * @return array<int, array<string, mixed>>
     */
    public function partnersFor(?int $stateId = null): array
    {
        return InsurancePartner::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->filter(function (InsurancePartner $partner) use ($stateId) {
                $states = $partner->states_served;

                return empty($states) || $stateId === null || in_array($stateId, (array) $states, true);
            })
            ->map(fn (InsurancePartner $partner) => [
                'id' => $partner->id,
                'name' => $partner->name,
                'slug' => $partner->slug,
                'logo' => $partner->logo,
                'description' => $partner->description,
                'coverage_types' => (array) ($partner->coverage_types ?? []),
            ])
            ->values()
            ->all();
    }

    public function capture(array $data, ?User $user = null): InsuranceEnquiry
    {
        return DB::transaction(function () use ($data, $user) {
            $product = ! empty($data['product_id']) ? Product::find($data['product_id']) : null;

            $enquiry = InsuranceEnquiry::create([
                'reference_no' => $this->nextReference(),
                'user_id' => $user?->id,
                'insurance_partner_id' => $data['insurance_partner_id'] ?? null,
                'product_id' => $product?->id,
                'applicant_name' => $data['name'],
                'mobile' => $data['mobile'],
                'registration_number' => $data['registration_number'] ?? null,
                'manufacturing_year' => $data['manufacturing_year'] ?? null,
                'coverage_type' => $data['coverage_type'] ?? 'comprehensive',
                'previous_policy_expiry' => $data['previous_policy_expiry'] ?? null,
                'has_claim_history' => (bool) ($data['has_claim_history'] ?? false),
                'idv_expected' => $data['idv_expected'] ?? null,
                'status' => 'new',
            ]);

            $this->leads->capture('insurance', [
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'mobile_verified' => $data['mobile_verified'] ?? false,
                'email' => $data['email'] ?? null,
                'state_id' => $data['state_id'] ?? null,
                'district_id' => $data['district_id'] ?? null,
                'city_id' => $data['city_id'] ?? null,
                'message' => $data['message'] ?? null,
                'meta' => [
                    'insurance_reference' => $enquiry->reference_no,
                    'coverage_type' => $enquiry->coverage_type,
                    'registration_number' => $enquiry->registration_number,
                ],
                'ip' => $data['ip'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ], $product, $user);

            return $enquiry->refresh();
        });
    }
}
