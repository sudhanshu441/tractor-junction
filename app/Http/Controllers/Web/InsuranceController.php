<?php

namespace App\Http\Controllers\Web;

use App\Domain\Auth\OtpService;
use App\Domain\Finance\Services\InsuranceService;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\State;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsuranceController extends Controller
{
    public function __construct(
        private readonly InsuranceService $insurance,
        private readonly OtpService $otp,
    ) {}

    /** Server-rendered: partners and cover types are indexable content. */
    public function index(Request $request): View
    {
        $stateId = $request->query('state_id') ? (int) $request->query('state_id') : null;

        return view('web.finance.insurance', [
            'partners' => $this->insurance->partnersFor($stateId),
            'coverageTypes' => InsuranceService::COVERAGE_TYPES,
            'states' => State::orderBy('name')->get(['id', 'name'])->toArray(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name'])->toArray(),
            'selectedState' => $stateId,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'otp' => ['required', 'digits_between:4,8'],
            'email' => ['nullable', 'email', 'max:150'],
            'insurance_partner_id' => ['nullable', 'exists:insurance_partners,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'registration_number' => ['nullable', 'string', 'max:20'],
            'manufacturing_year' => ['nullable', 'integer', 'min:1980', 'max:'.(date('Y') + 1)],
            'coverage_type' => ['required', 'in:comprehensive,third_party,own_damage'],
            'previous_policy_expiry' => ['nullable', 'date'],
            'has_claim_history' => ['nullable', 'boolean'],
            'idv_expected' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'state_id' => ['nullable', 'exists:states,id'],
            'district_id' => ['nullable', 'exists:districts,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $verification = $this->otp->verify($data['mobile'], $data['otp'], 'lead');

        if (! $verification['verified']) {
            return response()->json([
                'status' => 'error',
                'message' => __('That code is not correct or has expired.'),
            ], 422);
        }

        $enquiry = $this->insurance->capture([
            ...$data,
            'mobile_verified' => true,
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 255),
        ], $request->user());

        return response()->json([
            'status' => 'ok',
            'message' => __('Enquiry :reference registered. An advisor will call you with quotes.', [
                'reference' => $enquiry->reference_no,
            ]),
            'data' => ['reference' => $enquiry->reference_no],
        ]);
    }
}
