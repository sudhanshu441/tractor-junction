<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Finance\Services\LoanApplicationService;
use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\LoanDocument;
use App\Models\User;
use App\Support\DocumentVault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function __construct(
        private readonly LoanApplicationService $loans,
        private readonly DocumentVault $vault,
    ) {}

    public function index(): View
    {
        return view('admin.loans.index', [
            'counts' => [
                'all' => LoanApplication::count(),
                'open' => LoanApplication::open()->count(),
                'docs_pending' => LoanApplication::docsPending()->count(),
                'sanctioned' => LoanApplication::where('status', 'sanctioned')->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = LoanApplication::with(['financeable', 'assignee', 'district']);

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(fn ($q) => $q
                ->where('reference_no', 'like', "%{$search}%")
                ->orWhere('applicant_name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%"));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        $total = LoanApplication::count();
        $filtered = (clone $query)->count();
        $canSeeContact = $request->user()->can('leads.view_contact');

        $rows = $query->latest()
            ->skip((int) $request->input('start', 0))
            ->take(min(100, (int) $request->input('length', 25)))
            ->get()
            ->map(fn (LoanApplication $a) => [
                'reference' => $a->reference_no,
                'applicant' => $a->applicant_name,
                'mobile' => $canSeeContact ? $a->mobile : $this->mask($a->mobile),
                'amount' => '₹'.number_format((float) $a->loan_amount),
                'emi' => '₹'.number_format((float) $a->calculated_emi),
                'tenure' => $a->tenure_months.' mo',
                'district' => $a->district?->name ?? '—',
                'status' => $a->status,
                'assignee' => $a->assignee?->name ?? '—',
                'submitted' => $a->submitted_at?->diffForHumans() ?? '—',
                'detail_url' => route('admin.loans.show', $a),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    public function show(Request $request, LoanApplication $application): View
    {
        $application->load(['user', 'documents', 'statusLogs.changedBy', 'lenders',
            'financeable', 'state', 'district', 'city', 'assignee']);

        return view('admin.loans.show', [
            'application' => $application,
            'missing' => $this->loans->missingDocuments($application),
            'matchingLenders' => $this->loans->matchingLenders($application),
            'transitions' => LoanApplicationService::TRANSITIONS[$application->status] ?? [],
            'canSeeContact' => $request->user()->can('leads.view_contact'),
            'financeStaff' => User::staff()->active()->get(),
            'documentLinks' => $application->documents->mapWithKeys(fn ($doc) => [
                $doc->id => $this->vault->temporaryUrl('documents.loan', ['document' => $doc->id]),
            ]),
        ]);
    }

    public function changeStatus(Request $request, LoanApplication $application): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:under_review,docs_pending,sent_to_lender,sanctioned,rejected,disbursed,cancelled'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->loans->changeStatus($application, $data['status'], $data['remarks'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok', 'message' => __('Application updated.')]);
    }

    public function sendToLenders(Request $request, LoanApplication $application): JsonResponse
    {
        $data = $request->validate([
            'lender_ids' => ['required', 'array', 'min:1'],
            'lender_ids.*' => ['integer', 'exists:lenders,id'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($missing = $this->loans->missingDocuments($application)) {
            return response()->json([
                'status' => 'error',
                'message' => __('Documents still missing: :list', [
                    'list' => implode(', ', array_map(fn ($d) => str_replace('_', ' ', $d), $missing)),
                ]),
            ], 422);
        }

        try {
            $this->loans->sendToLenders($application, $data['lender_ids'], $data['remarks'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok', 'message' => __('Sent to :count lender(s).', ['count' => count($data['lender_ids'])])]);
    }

    public function lenderDecision(Request $request, LoanApplication $application): JsonResponse
    {
        $data = $request->validate([
            'lender_id' => ['required', 'exists:lenders,id'],
            'decision' => ['required', 'in:acknowledged,sanctioned,rejected'],
            'sanctioned_amount' => ['nullable', 'numeric', 'min:0'],
            'offered_rate' => ['nullable', 'numeric', 'min:0', 'max:36'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $this->loans->recordLenderDecision(
            $application, (int) $data['lender_id'], $data['decision'],
            $data['sanctioned_amount'] ?? null, $data['offered_rate'] ?? null, $data['remarks'] ?? null,
        );

        return response()->json(['status' => 'ok', 'message' => __('Lender decision recorded.')]);
    }

    public function verifyDocument(Request $request, LoanDocument $document): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:verified,rejected'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $document->update([
            'status' => $data['status'],
            'remarks' => $data['remarks'] ?? null,
            'verified_by' => $request->user()->id,
        ]);

        return response()->json(['status' => 'ok', 'message' => __('Document :status.', ['status' => $data['status']])]);
    }

    private function mask(?string $mobile): string
    {
        if (! $mobile || strlen($mobile) < 10) {
            return (string) $mobile;
        }

        return substr($mobile, 0, 2).str_repeat('X', strlen($mobile) - 4).substr($mobile, -2);
    }
}
