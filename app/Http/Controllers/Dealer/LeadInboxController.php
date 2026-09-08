<?php

namespace App\Http\Controllers\Dealer;

use App\Domain\Lead\Services\LeadService;
use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * A dealer lives here. The SLA countdown is visible because an unanswered lead
 * is reassigned to a competitor.
 */
class LeadInboxController extends Controller
{
    public function __construct(private readonly LeadService $leads) {}

    public function index(Request $request): View
    {
        $dealer = DashboardController::resolveDealer($request);

        abort_unless($dealer, 403);

        $status = $request->query('status');

        $leads = Lead::whereIn('id', $dealer->leadAssignments()->select('lead_id'))
            ->with(['leadable', 'district', 'currentAssignment'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(20)->withQueryString();

        return view('dealer.leads.index', [
            'dealer' => $dealer,
            'leads' => $leads,
            'status' => $status,
            'slaMinutes' => (int) config('kj.leads.response_sla_minutes'),
            'counts' => collect(['new', 'assigned', 'contacted', 'qualified', 'converted', 'lost'])
                ->mapWithKeys(fn ($s) => [$s => Lead::whereIn('id', $dealer->leadAssignments()->select('lead_id'))
                    ->where('status', $s)->count()])->all(),
        ]);
    }

    public function show(Request $request, Lead $lead): View
    {
        $dealer = $this->authorizeLead($request, $lead);

        return view('dealer.leads.show', [
            'dealer' => $dealer,
            'lead' => $lead->load(['leadable', 'activities.user', 'district', 'city', 'currentAssignment']),
            'statuses' => LeadService::TRANSITIONS[$lead->status] ?? [],
        ]);
    }

    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $this->authorizeLead($request, $lead);

        $data = $request->validate([
            'status' => ['required', 'in:contacted,qualified,converted,lost'],
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
        $this->authorizeLead($request, $lead);

        $data = $request->validate([
            'note' => ['required', 'string', 'max:1000'],
            'activity' => ['nullable', 'in:note,call,sms,whatsapp,visit'],
            'next_follow_up_at' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        $this->leads->addActivity($lead, $data['activity'] ?? 'note', $data['note']);

        if (! empty($data['next_follow_up_at'])) {
            $lead->forceFill(['next_follow_up_at' => $data['next_follow_up_at']])->save();
        }

        return response()->json(['status' => 'ok', 'message' => __('Saved.')]);
    }

    /** A dealer may only ever touch a lead that was assigned to them. */
    private function authorizeLead(Request $request, Lead $lead): Dealer
    {
        $dealer = DashboardController::resolveDealer($request);

        abort_unless($dealer, 403);
        abort_unless($dealer->leadAssignments()->where('lead_id', $lead->id)->exists(), 403);

        return $dealer;
    }
}
