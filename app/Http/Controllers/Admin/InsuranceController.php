<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceEnquiry;
use App\Models\InsurancePartner;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The insurance desk.
 *
 * Enquiries were being captured and routed as leads from Phase 4 but had no
 * screen of their own, so the cover requested, the machine and the policy
 * expiry — everything an advisor needs before calling back — were invisible.
 */
class InsuranceController extends Controller
{
    public const TRANSITIONS = [
        'new' => ['contacted', 'lost'],
        'contacted' => ['quoted', 'lost'],
        'quoted' => ['converted', 'lost'],
        'converted' => [],
        'lost' => ['contacted'],
    ];

    public function index(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.insurance.index', [
            'enquiries' => InsuranceEnquiry::with(['partner', 'product.brand', 'assignee', 'user'])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($request->query('coverage'), fn ($q, $c) => $q->where('coverage_type', $c))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'status' => $status,
            'partners' => InsurancePartner::where('is_active', true)->orderBy('name')->get(),
            'advisors' => User::staff()->active()->get()
                ->filter(fn (User $u) => $u->can('insurance.assign') || $u->can('insurance.edit'))
                ->values(),
            'counts' => [
                'new' => InsuranceEnquiry::where('status', 'new')->count(),
                'contacted' => InsuranceEnquiry::where('status', 'contacted')->count(),
                'quoted' => InsuranceEnquiry::where('status', 'quoted')->count(),
                'converted' => InsuranceEnquiry::where('status', 'converted')->count(),
                'lost' => InsuranceEnquiry::where('status', 'lost')->count(),
            ],
            'expiringSoon' => InsuranceEnquiry::open()
                ->whereNotNull('previous_policy_expiry')
                ->whereBetween('previous_policy_expiry', [today(), today()->addDays(30)])
                ->count(),
        ]);
    }

    /**
     * A status change, an insurer and an owner in one call — an advisor picking
     * up an enquiry usually does all three at once.
     */
    public function update(Request $request, InsuranceEnquiry $enquiry): JsonResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'in:new,contacted,quoted,converted,lost'],
            'insurance_partner_id' => ['nullable', 'exists:insurance_partners,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        if (($data['status'] ?? null) && $data['status'] !== $enquiry->status) {
            $allowed = self::TRANSITIONS[$enquiry->status] ?? [];

            if (! in_array($data['status'], $allowed, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => __('An enquiry cannot go from :from to :to.', [
                        'from' => $enquiry->status, 'to' => $data['status'],
                    ]),
                ], 422);
            }
        }

        $enquiry->update(array_filter($data, fn ($v) => $v !== null));

        activity()->performedOn($enquiry)->causedBy($request->user())
            ->withProperties($data)->log('Updated insurance enquiry');

        return response()->json(['status' => 'ok', 'message' => __('Enquiry updated.')]);
    }
}
