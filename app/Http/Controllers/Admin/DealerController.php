<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Dealer\Services\DealerService;
use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Support\DocumentVault;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealerController extends Controller
{
    public function __construct(
        private readonly DealerService $dealers,
        private readonly DocumentVault $vault,
    ) {}

    public function index(): View
    {
        return view('admin.dealers.index', [
            'counts' => [
                'all' => Dealer::count(),
                'pending' => Dealer::where('verification_status', 'pending')->count(),
                'verified' => Dealer::where('verification_status', 'verified')->count(),
                'suspended' => Dealer::where('verification_status', 'suspended')->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Dealer::with(['city', 'state', 'brands'])->withCount('leadAssignments');

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(fn ($q) => $q
                ->where('business_name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%"));
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('verification_status', $status);
        }

        $total = Dealer::count();
        $filtered = (clone $query)->count();
        $canSeeContact = $request->user()->can('leads.view_contact');

        $rows = $query->latest()
            ->skip((int) $request->input('start', 0))
            ->take(min(100, (int) $request->input('length', 25)))
            ->get()
            ->map(fn (Dealer $d) => [
                'code' => $d->code,
                'name' => $d->display_name,
                'type' => str_replace('_', ' ', $d->dealer_type),
                'brands' => $d->brands->pluck('name')->take(3)->implode(', ') ?: '—',
                'city' => $d->city?->name ?? $d->state?->name ?? '—',
                'mobile' => $canSeeContact ? $d->mobile : $d->masked_mobile,
                'leads' => $d->lead_assignments_count,
                'score' => number_format((float) $d->response_score, 1),
                'status' => $d->verification_status,
                'detail_url' => route('admin.dealers.show', $d),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    public function show(Request $request, Dealer $dealer): View
    {
        $dealer->load(['brands', 'branches.city', 'documents', 'owner', 'city', 'district', 'state',
            'activeSubscription.plan', 'inventory.product']);

        return view('admin.dealers.show', [
            'dealer' => $dealer,
            'usage' => $this->dealers->planUsage($dealer),
            'canSeeContact' => $request->user()->can('leads.view_contact'),
            // Signed, short-lived links — the files themselves are not web-reachable.
            'documentLinks' => $dealer->documents->mapWithKeys(fn ($doc) => [
                $doc->id => $this->vault->temporaryUrl('documents.dealer', ['document' => $doc->id]),
            ]),
            'transitions' => DealerService::TRANSITIONS[$dealer->verification_status] ?? [],
        ]);
    }

    public function verify(Request $request, Dealer $dealer): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:verified,rejected,suspended'],
            'remarks' => ['required_unless:status,verified', 'nullable', 'string', 'max:500'],
        ], [
            'remarks.required_unless' => __('A reason is required — the dealer sees it.'),
        ]);

        try {
            $this->dealers->changeStatus($dealer, $data['status'], $data['remarks'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => match ($data['status']) {
                'verified' => __('Dealer verified — they can now receive leads.'),
                'rejected' => __('Registration rejected.'),
                'suspended' => __('Dealer suspended — lead routing stopped.'),
            },
        ]);
    }
}
