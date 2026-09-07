<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Lead\Services\LeadService;
use App\Domain\Lead\Services\RoutingEngine;
use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\Product;
use App\Models\State;
use App\Models\UsedListing;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leads,
        private readonly RoutingEngine $routing,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.leads.index', [
            'states' => State::active()->orderBy('name')->pluck('name', 'id'),
            'counts' => [
                'all' => Lead::count(),
                'unassigned' => Lead::unassigned()->count(),
                'due_today' => Lead::dueToday()->count(),
                'duplicates' => Lead::where('status', 'duplicate')->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = Lead::with(['leadable', 'district', 'currentAssignment.dealer', 'currentAssignment.user']);

        if ($search = trim((string) $request->input('search.value'))) {
            $query->where(fn ($q) => $q
                ->where('reference_no', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%"));
        }

        foreach (['type', 'status'] as $field) {
            if ($value = $request->string($field)->toString()) {
                $query->where($field, $value);
            }
        }

        if ($stateId = $request->integer('state_id')) {
            $query->where('state_id', $stateId);
        }

        if ($request->boolean('unassigned')) {
            $query->unassigned();
        }

        if ($request->boolean('due_today')) {
            $query->dueToday();
        }

        $total = Lead::count();
        $filtered = (clone $query)->count();

        // A sales executive sees only what they were given; PII is masked
        // for anyone without leads.view_contact.
        $user = $request->user();

        if (! $user->can('leads.assign') && ! $user->hasRole(['super-admin', 'admin'])) {
            $query->whereHas('assignments', fn ($q) => $q->where('user_id', $user->id));
        }

        $canSeeContact = $user->can('leads.view_contact');

        $rows = $query->latest()
            ->skip((int) $request->input('start', 0))
            ->take(min(100, (int) $request->input('length', 25)))
            ->get()
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'reference' => $lead->reference_no,
                'type' => $lead->type,
                'name' => $lead->name,
                'mobile' => $canSeeContact ? $lead->mobile : $lead->masked_mobile,
                'about' => $this->describeSubject($lead),
                'district' => $lead->district?->name ?? '—',
                'age' => $lead->created_at->diffForHumans(null, true),
                'assignee' => $this->describeAssignee($lead),
                'status' => $lead->status,
                'detail_url' => route('admin.leads.show', $lead),
            ]);

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows,
        ]);
    }

    public function show(Request $request, Lead $lead): View
    {
        $lead->load(['leadable', 'assignments.dealer', 'assignments.user', 'activities.user',
            'state', 'district', 'city', 'source', 'duplicateOf']);

        return view('admin.leads.show', [
            'lead' => $lead,
            'canSeeContact' => $request->user()->can('leads.view_contact'),
            'dealers' => Dealer::verified()
                ->when($lead->district_id, fn ($q) => $q->where('district_id', $lead->district_id))
                ->orderBy('display_name')->limit(50)->get(),
            'staff' => User::staff()->active()->orderBy('name')->get(),
            'statuses' => LeadService::TRANSITIONS[$lead->status] ?? [],
        ]);
    }

    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'assignee_type' => ['required', 'in:dealer,staff'],
            'dealer_id' => ['required_if:assignee_type,dealer', 'nullable', 'exists:dealers,id'],
            'user_id' => ['required_if:assignee_type,staff', 'nullable', 'exists:users,id'],
        ]);

        LeadAssignment::create([
            'lead_id' => $lead->id,
            'assignee_type' => $data['assignee_type'],
            'dealer_id' => $data['dealer_id'] ?? null,
            'user_id' => $data['user_id'] ?? null,
            'assigned_by' => $request->user()->id,
            'status' => 'pending',
            'assigned_at' => now(),
        ]);

        if ($lead->status === 'new') {
            $lead->forceFill(['status' => 'assigned'])->save();
        }

        $this->leads->addActivity($lead, 'assignment', __('Reassigned by :user', ['user' => $request->user()->name]));

        return response()->json(['status' => 'ok', 'message' => __('Lead assigned.')]);
    }

    /** Bulk assign: run the routing engine over a selection. */
    public function bulkRoute(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_ids' => ['required', 'array', 'max:100'],
            'lead_ids.*' => ['integer', 'exists:leads,id'],
        ]);

        $routed = 0;
        $unrouted = 0;

        foreach (Lead::whereIn('id', $data['lead_ids'])->with('leadable')->get() as $lead) {
            $this->routing->route($lead) ? $routed++ : $unrouted++;
        }

        return response()->json([
            'status' => 'ok',
            'message' => __(':routed routed, :unrouted could not be matched.', [
                'routed' => $routed, 'unrouted' => $unrouted,
            ]),
        ]);
    }

    public function changeStatus(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:contacted,qualified,converted,lost,invalid,duplicate'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->leads->changeStatus($lead, $data['status'], $data['reason'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok', 'message' => __('Lead updated.')]);
    }

    public function addNote(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
            'activity' => ['nullable', 'in:note,call,sms,whatsapp,email,visit'],
            'next_follow_up_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $this->leads->addActivity($lead, $data['activity'] ?? 'note', $data['note']);

        if (! empty($data['next_follow_up_at'])) {
            $lead->forceFill(['next_follow_up_at' => $data['next_follow_up_at']])->save();
        }

        return response()->json(['status' => 'ok', 'message' => __('Note added.')]);
    }

    public function merge(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate(['original_id' => ['required', 'exists:leads,id', 'different:'.$lead->id]]);

        $this->leads->merge($lead, Lead::findOrFail($data['original_id']));

        return response()->json(['status' => 'ok', 'message' => __('Merged into the original lead.')]);
    }

    /** Streamed so a large export never holds the whole set in memory. */
    public function export(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('leads.export'), 403);

        $canSeeContact = $request->user()->can('leads.view_contact');
        $filename = 'leads-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($request, $canSeeContact) {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, ['Reference', 'Type', 'Name', 'Mobile', 'About', 'District',
                'Status', 'Assignee', 'Created']);

            Lead::with(['leadable', 'district', 'currentAssignment.dealer', 'currentAssignment.user'])
                ->when($request->string('status')->toString(), fn ($q, $s) => $q->where('status', $s))
                ->chunk(500, function ($leads) use ($handle, $canSeeContact) {
                    foreach ($leads as $lead) {
                        fputcsv($handle, [
                            $lead->reference_no,
                            $lead->type,
                            $lead->name,
                            $canSeeContact ? $lead->mobile : $lead->masked_mobile,
                            $this->describeSubject($lead),
                            $lead->district?->name,
                            $lead->status,
                            $this->describeAssignee($lead),
                            $lead->created_at->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function describeSubject(Lead $lead): string
    {
        return match (true) {
            $lead->leadable instanceof Product => $lead->leadable->full_name,
            $lead->leadable instanceof UsedListing => $lead->leadable->reference_no.' · '.$lead->leadable->title,
            $lead->leadable instanceof Dealer => $lead->leadable->display_name,
            default => '—',
        };
    }

    private function describeAssignee(Lead $lead): string
    {
        $assignment = $lead->currentAssignment;

        return $assignment?->dealer?->display_name ?? $assignment?->user?->name ?? '—';
    }
}
