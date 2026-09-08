<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Marketplace\Services\InspectionService;
use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\UsedListing;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InspectionController extends Controller
{
    public function __construct(private readonly InspectionService $inspections) {}

    public function index(Request $request): View
    {
        return view('admin.inspections.index', [
            'inspections' => Inspection::with(['listing.brand', 'listing.city', 'inspector'])
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->latest()->paginate(25)->withQueryString(),
            'inspectors' => User::staff()->active()->get()
                ->filter(fn (User $u) => $u->hasRole('inspector') || $u->hasRole('super-admin')),
            'counts' => [
                'requested' => Inspection::where('status', 'requested')->count(),
                'scheduled' => Inspection::where('status', 'scheduled')->count(),
                'completed' => Inspection::where('status', 'completed')->whereNull('approved_by')->count(),
            ],
        ]);
    }

    public function request(Request $request, UsedListing $listing): JsonResponse
    {
        $inspection = $this->inspections->request($listing);

        return response()->json([
            'status' => 'ok',
            'message' => __('Inspection requested (:ref).', ['ref' => $inspection->reference_no]),
        ]);
    }

    public function schedule(Request $request, Inspection $inspection): JsonResponse
    {
        $data = $request->validate([
            'inspector_id' => ['required', 'exists:users,id'],
            'scheduled_at' => ['required', 'date', 'after_or_equal:today'],
        ]);

        try {
            $this->inspections->schedule($inspection, (int) $data['inspector_id'], $data['scheduled_at']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json(['status' => 'ok', 'message' => __('Inspection scheduled and the seller told.')]);
    }

    /** The inspector's own form: checklist scores become the grade. */
    public function form(Request $request, Inspection $inspection): View
    {
        abort_unless(
            $inspection->inspector_id === $request->user()->id || $request->user()->can('inspections.approve'),
            403,
        );

        return view('admin.inspections.form', [
            'inspection' => $inspection->load(['listing.images', 'listing.brand', 'items']),
            'checklist' => InspectionChecklistItem::where('is_active', true)
                ->orderBy('sort_order')->get()->groupBy('section'),
            'scores' => $inspection->items->keyBy('inspection_checklist_item_id'),
        ]);
    }

    public function complete(Request $request, Inspection $inspection): JsonResponse
    {
        abort_unless(
            $inspection->inspector_id === $request->user()->id || $request->user()->can('inspections.approve'),
            403,
        );

        $data = $request->validate([
            'scores' => ['required', 'array'],
            'scores.*.score' => ['required', 'integer', 'min:0', 'max:10'],
            'scores.*.remarks' => ['nullable', 'string', 'max:300'],
            'summary' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($inspection->status === 'scheduled') {
            $this->inspections->changeStatus($inspection, 'in_progress');
        }

        try {
            $inspection = $this->inspections->complete($inspection->refresh(), $data['scores'], $data['summary'] ?? null);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'ok',
            'message' => __('Report saved — score :score, grade :grade. It needs approval before the badge shows.', [
                'score' => number_format((float) $inspection->overall_score, 1),
                'grade' => $inspection->grade,
            ]),
            'data' => ['grade' => $inspection->grade, 'score' => (float) $inspection->overall_score],
        ]);
    }

    /** Approval is separate from submission, so a bad report cannot self-publish. */
    public function approve(Request $request, Inspection $inspection): JsonResponse
    {
        try {
            $this->inspections->approve($inspection);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        activity()->performedOn($inspection)->causedBy($request->user())->log('Approved inspection report');

        return response()->json(['status' => 'ok', 'message' => __('Approved — the listing now shows the verified badge.')]);
    }
}
