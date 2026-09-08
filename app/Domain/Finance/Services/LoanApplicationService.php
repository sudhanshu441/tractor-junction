<?php

namespace App\Domain\Finance\Services;

use App\Domain\Notification\NotificationDispatcher;
use App\Models\Lender;
use App\Models\LoanApplication;
use App\Models\LoanDocument;
use App\Models\LoanStatusLog;
use App\Support\DocumentVault;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Owns the loan application state machine and the lender hand-off.
 *
 *   draft → submitted → under_review ⇄ docs_pending → sent_to_lender
 *         → sanctioned → disbursed | rejected | cancelled
 *
 * Every move writes a log row and tells the applicant, because a loan the
 * borrower cannot track is a support call waiting to happen.
 */
class LoanApplicationService
{
    public const TRANSITIONS = [
        'draft' => ['submitted', 'cancelled'],
        'submitted' => ['under_review', 'docs_pending', 'cancelled'],
        'under_review' => ['docs_pending', 'sent_to_lender', 'rejected', 'cancelled'],
        'docs_pending' => ['under_review', 'cancelled', 'rejected'],
        'sent_to_lender' => ['sanctioned', 'rejected', 'docs_pending'],
        'sanctioned' => ['disbursed', 'cancelled'],
        'disbursed' => [],
        'rejected' => [],
        'cancelled' => [],
    ];

    /** Documents every application needs before it reaches a lender. */
    public const REQUIRED_DOCUMENTS = ['aadhaar', 'pan', 'land_record', 'bank_statement'];

    public function __construct(
        private readonly EmiCalculator $emi,
        private readonly DocumentVault $vault,
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function nextReference(): string
    {
        $last = LoanApplication::withTrashed()->max('id') ?? 0;

        return 'KJ-LN-'.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }

    /** Creates or updates the applicant's draft; each wizard step calls this. */
    public function saveDraft(array $data, ?LoanApplication $application = null, ?int $userId = null, ?Model $financeable = null): LoanApplication
    {
        $data = $this->withDerivedFigures($data, $application);

        if ($application) {
            $application->update($data);

            return $application->refresh();
        }

        return LoanApplication::create([
            ...$data,
            'reference_no' => $this->nextReference(),
            'user_id' => $userId,
            'status' => 'draft',
            'financeable_type' => $financeable?->getMorphClass(),
            'financeable_id' => $financeable?->getKey(),
        ]);
    }

    /** The EMI on the record is always the server's, never the browser's. */
    private function withDerivedFigures(array $data, ?LoanApplication $application): array
    {
        $price = (float) ($data['machinery_price'] ?? $application?->machinery_price ?? 0);
        $down = (float) ($data['down_payment'] ?? $application?->down_payment ?? 0);
        $rate = (float) ($data['expected_interest_rate'] ?? $application?->expected_interest_rate ?? 11.5);
        $tenure = (int) ($data['tenure_months'] ?? $application?->tenure_months ?? 60);

        // A wizard step can be saved before the machine is chosen. loan_amount is
        // NOT NULL, so a zero has to be written rather than left absent.
        if ($price <= 0) {
            return [...$data, 'loan_amount' => 0, 'calculated_emi' => 0];
        }

        $result = $this->emi->calculate($price, $down, $rate, $tenure);

        return [
            ...$data,
            'loan_amount' => $result['loan_amount'],
            'calculated_emi' => $result['emi'],
            'expected_interest_rate' => $rate,
            'tenure_months' => $tenure,
        ];
    }

    /** KYC identifiers are masked before they are ever written. */
    public function maskIdentifiers(array $data): array
    {
        if (isset($data['pan'])) {
            $data['pan_masked'] = DocumentVault::mask($data['pan']);
            unset($data['pan']);
        }

        if (isset($data['aadhaar'])) {
            $data['aadhaar_masked'] = DocumentVault::mask($data['aadhaar']);
            unset($data['aadhaar']);
        }

        return $data;
    }

    public function submit(LoanApplication $application): LoanApplication
    {
        foreach (['applicant_name', 'mobile', 'machinery_price', 'loan_amount'] as $required) {
            if (blank($application->{$required})) {
                throw new \InvalidArgumentException("A loan application cannot be submitted without {$required}.");
            }
        }

        $application->forceFill(['submitted_at' => now()])->save();

        // Missing documents do not block submission — the finance desk chases
        // them, and an application held back for a scan is an application lost.
        $missing = $this->missingDocuments($application);

        return $this->changeStatus($application, 'submitted', $missing === []
            ? __('Submitted by applicant')
            : __('Submitted by applicant — awaiting :docs', ['docs' => implode(', ', $missing)]));
    }

    /** @return array<int, string> document types still outstanding */
    public function missingDocuments(LoanApplication $application): array
    {
        $uploaded = $application->documents()
            ->whereIn('status', ['pending', 'verified'])
            ->pluck('doc_type')->all();

        return array_values(array_diff(self::REQUIRED_DOCUMENTS, $uploaded));
    }

    public function attachDocument(LoanApplication $application, UploadedFile $file, string $type): LoanDocument
    {
        $path = $this->vault->store($file, 'loan-documents/'.$application->reference_no);

        return $application->documents()->updateOrCreate(
            ['doc_type' => $type],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'status' => 'pending',
                'remarks' => null,
                'verified_by' => null,
            ],
        );
    }

    /** @throws \InvalidArgumentException on an illegal transition */
    public function changeStatus(LoanApplication $application, string $to, ?string $remarks = null): LoanApplication
    {
        $from = $application->status;

        if ($from !== $to && ! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new \InvalidArgumentException("Cannot move a loan application from {$from} to {$to}.");
        }

        return DB::transaction(function () use ($application, $from, $to, $remarks) {
            $application->forceFill([
                'status' => $to,
                'remarks' => $remarks ?? $application->remarks,
                'decided_at' => in_array($to, ['sanctioned', 'rejected', 'disbursed'], true) ? now() : $application->decided_at,
            ])->save();

            LoanStatusLog::create([
                'loan_application_id' => $application->id,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => Auth::id(),
                'remarks' => $remarks,
            ]);

            $this->notifyApplicant($application, $to, $remarks);

            return $application->refresh();
        });
    }

    private function notifyApplicant(LoanApplication $application, string $to, ?string $remarks): void
    {
        $user = $application->user;

        if (! $user) {
            return;
        }

        $payload = ['reference' => $application->reference_no];

        match ($to) {
            'submitted' => $this->notifications->send('loan.submitted', $user, $payload),
            'docs_pending' => $this->notifications->send('loan.docs_pending', $user, [
                ...$payload,
                'documents' => implode(', ', array_map(
                    fn ($d) => str_replace('_', ' ', $d),
                    $this->missingDocuments($application),
                )) ?: ($remarks ?? ''),
            ]),
            'sanctioned' => $this->notifications->send('loan.sanctioned', $user, [
                ...$payload, 'amount' => number_format((float) $application->loan_amount),
            ]),
            'rejected' => $this->notifications->send('loan.rejected', $user, $payload),
            'disbursed' => $this->notifications->send('loan.disbursed', $user, $payload),
            default => null,
        };
    }

    /**
     * Lenders whose published criteria actually fit this application, so the
     * finance desk is not guessing.
     */
    public function matchingLenders(LoanApplication $application): Collection
    {
        $amount = (float) $application->loan_amount;
        $tenure = (int) $application->tenure_months;
        $stateCode = $application->state?->code;

        return Lender::where('is_active', true)->get()->filter(function (Lender $lender) use ($amount, $tenure, $stateCode) {
            if ($lender->amount_min && $amount < (float) $lender->amount_min) {
                return false;
            }

            if ($lender->amount_max && $amount > (float) $lender->amount_max) {
                return false;
            }

            if ($tenure < $lender->tenure_min_months || $tenure > $lender->tenure_max_months) {
                return false;
            }

            $states = $lender->states_served;

            // An empty list means the lender operates nationally.
            return blank($states) || ! $stateCode || in_array($stateCode, (array) $states, true);
        })->values();
    }

    /** Sends the application to one or more lenders and records each hand-off. */
    public function sendToLenders(LoanApplication $application, array $lenderIds, ?string $remarks = null): LoanApplication
    {
        DB::transaction(function () use ($application, $lenderIds, $remarks) {
            foreach (Lender::whereIn('id', $lenderIds)->where('is_active', true)->get() as $lender) {
                $application->lenders()->syncWithoutDetaching([
                    $lender->id => ['status' => 'sent', 'sent_at' => now(), 'remarks' => $remarks],
                ]);
            }
        });

        return $this->changeStatus($application, 'sent_to_lender', $remarks);
    }

    public function recordLenderDecision(
        LoanApplication $application,
        int $lenderId,
        string $decision,
        ?float $amount = null,
        ?float $rate = null,
        ?string $remarks = null,
    ): LoanApplication {
        $application->lenders()->updateExistingPivot($lenderId, [
            'status' => $decision,
            'sanctioned_amount' => $amount,
            'offered_rate' => $rate,
            'remarks' => $remarks,
        ]);

        // One sanction is enough; a rejection only closes the application when
        // no other lender is still considering it.
        if ($decision === 'sanctioned') {
            return $this->changeStatus($application, 'sanctioned', $remarks);
        }

        $stillOpen = $application->lenders()
            ->wherePivotIn('status', ['sent', 'acknowledged'])->exists();

        if ($decision === 'rejected' && ! $stillOpen) {
            return $this->changeStatus($application, 'rejected', $remarks ?? __('Declined by all lenders'));
        }

        return $application->refresh();
    }
}
