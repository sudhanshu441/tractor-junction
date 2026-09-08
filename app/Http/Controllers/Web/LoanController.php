<?php

namespace App\Http\Controllers\Web;

use App\Domain\Auth\OtpService;
use App\Domain\Finance\Services\EmiCalculator;
use App\Domain\Finance\Services\LoanApplicationService;
use App\Http\Controllers\Controller;
use App\Models\Lender;
use App\Models\LoanApplication;
use App\Models\Product;
use App\Models\State;
use App\Models\UsedListing;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Four-step loan application, saved server-side at every step like the sell
 * wizard, because the same farmer on the same connection fills both.
 */
class LoanController extends Controller
{
    private const SESSION_KEY = 'kj.loan.draft';

    public function __construct(
        private readonly LoanApplicationService $loans,
        private readonly EmiCalculator $emi,
        private readonly OtpService $otp,
    ) {}

    public function hub(): View
    {
        return view('web.finance.hub', [
            'lenders' => Lender::where('is_active', true)->orderBy('sort_order')->get(),
            'defaultRate' => config('kj.finance.default_interest_rate'),
        ]);
    }

    public function apply(Request $request): View
    {
        $financeable = $this->resolveFinanceable($request);

        return view('web.finance.apply', [
            'draft' => $this->currentDraft($request),
            'financeable' => $financeable,
            'states' => State::active()->orderBy('name')->get(),
            'defaultRate' => config('kj.finance.default_interest_rate'),
            'requiredDocuments' => LoanApplicationService::REQUIRED_DOCUMENTS,
            'maxKb' => config('kj.documents.max_size_kb'),
        ]);
    }

    /** A quick yes/no before asking for twenty fields. */
    public function eligibility(Request $request): JsonResponse
    {
        $data = $request->validate([
            'annual_income' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'machinery_price' => ['required', 'numeric', 'min:10000'],
            'down_payment' => ['required', 'numeric', 'min:0', 'lte:machinery_price'],
            'age' => ['nullable', 'integer', 'min:16', 'max:100'],
        ]);

        return response()->json([
            'status' => 'ok',
            'data' => $this->emi->eligibility(
                (float) $data['annual_income'],
                (float) $data['machinery_price'],
                (float) $data['down_payment'],
                $data['age'] ?? null,
            ),
        ]);
    }

    public function saveStep(Request $request, int $step): JsonResponse
    {
        $draft = $this->currentDraft($request);
        $data = $this->loans->maskIdentifiers($this->validateStep($request, $step));

        $draft = $this->loans->saveDraft(
            $data, $draft, Auth::id(), $draft ? null : $this->resolveFinanceable($request),
        );

        $request->session()->put(self::SESSION_KEY, $draft->id);

        return response()->json([
            'status' => 'ok',
            'message' => __('Saved.'),
            'data' => [
                'draft_id' => $draft->id,
                'reference' => $draft->reference_no,
                'emi' => (float) $draft->calculated_emi,
                'loan_amount' => (float) $draft->loan_amount,
                'next_step' => $step + 1,
                'missing_documents' => $this->loans->missingDocuments($draft),
            ],
        ]);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        $draft = $this->currentDraft($request);

        if (! $draft) {
            return response()->json(['status' => 'error', 'message' => __('Start the form before adding documents.')], 422);
        }

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.config('kj.documents.max_size_kb')],
            'doc_type' => ['required', 'in:aadhaar,pan,land_record,bank_statement,income_proof,photo,quotation,rc,other'],
        ]);

        $document = $this->loans->attachDocument($draft, $request->file('document'), $request->string('doc_type')->toString());

        return response()->json([
            'status' => 'ok',
            'message' => __('Document uploaded.'),
            'data' => [
                'doc_type' => $document->doc_type,
                'missing_documents' => $this->loans->missingDocuments($draft->refresh()),
            ],
        ]);
    }

    public function submit(Request $request): JsonResponse
    {
        $draft = $this->currentDraft($request);

        if (! $draft) {
            return response()->json(['status' => 'error', 'message' => __('Your draft has expired. Please start again.')], 422);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'otp' => ['required', 'digits_between:4,8'],
        ]);

        $verification = $this->otp->verify($data['mobile'], $data['otp'], 'loan');

        if (! $verification['verified']) {
            return response()->json([
                'status' => 'error',
                'message' => match ($verification['reason']) {
                    'incorrect' => __('That code is not correct.'),
                    'too_many_attempts' => __('Too many wrong attempts. Request a new code.'),
                    default => __('That code has expired. Request a new one.'),
                },
            ], 422);
        }

        $applicant = $this->resolveApplicant($data, $request);

        $draft->forceFill([
            'user_id' => $applicant->id,
            'applicant_name' => $data['name'],
            'mobile' => $data['mobile'],
        ])->save();

        try {
            $this->loans->submit($draft);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $request->session()->forget(self::SESSION_KEY);

        return response()->json([
            'status' => 'ok',
            'message' => __('Your application is submitted.'),
            'data' => ['redirect' => route('loan.submitted', $draft->reference_no)],
        ]);
    }

    public function submitted(string $reference): View
    {
        $application = LoanApplication::where('reference_no', $reference)->firstOrFail();

        return view('web.finance.submitted', [
            'application' => $application,
            'missing' => $this->loans->missingDocuments($application),
        ]);
    }

    // ----- internals -----

    private function currentDraft(Request $request): ?LoanApplication
    {
        $id = $request->session()->get(self::SESSION_KEY);

        return $id ? LoanApplication::where('id', $id)->where('status', 'draft')->first() : null;
    }

    private function resolveFinanceable(Request $request): ?Model
    {
        if ($productId = $request->integer('product')) {
            return Product::with('brand')->find($productId);
        }

        if ($listingId = $request->integer('listing')) {
            return UsedListing::find($listingId);
        }

        return null;
    }

    private function validateStep(Request $request, int $step): array
    {
        return match ($step) {
            1 => $request->validate([
                'applicant_name' => ['required', 'string', 'max:100'],
                'mobile' => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
                'email' => ['nullable', 'email', 'max:150'],
                'date_of_birth' => ['nullable', 'date', 'before:today'],
                'purpose' => ['required', 'in:new_purchase,used_purchase,refinance'],
            ]),
            2 => $request->validate([
                'machinery_price' => ['required', 'numeric', 'min:10000', 'max:99999999'],
                'down_payment' => ['required', 'numeric', 'min:0', 'lte:machinery_price'],
                'tenure_months' => ['required', 'integer', 'min:12', 'max:84'],
                'expected_interest_rate' => ['nullable', 'numeric', 'min:0', 'max:36'],
            ]),
            3 => $request->validate([
                'annual_income' => ['required', 'numeric', 'min:0', 'max:99999999'],
                'income_source' => ['required', 'in:farming,business,salary,other'],
                'land_holding_acres' => ['nullable', 'numeric', 'min:0', 'max:9999'],
                'state_id' => ['required', 'exists:states,id'],
                'district_id' => ['required', 'exists:districts,id'],
                'city_id' => ['nullable', 'exists:cities,id'],
                'address' => ['nullable', 'string', 'max:500'],
                // Collected to mask, never stored raw.
                'pan' => ['nullable', 'string', 'max:12'],
                'aadhaar' => ['nullable', 'string', 'max:14'],
            ]),
            4 => [],
            default => abort(404),
        };
    }

    private function resolveApplicant(array $data, Request $request): User
    {
        if ($user = $request->user()) {
            return $user;
        }

        $user = User::where('mobile', $data['mobile'])->first();

        if (! $user) {
            $user = User::create([
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'mobile_verified_at' => now(),
                'user_type' => 'customer',
                'is_active' => true,
            ]);
            $user->assignRole('customer');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return $user;
    }
}
