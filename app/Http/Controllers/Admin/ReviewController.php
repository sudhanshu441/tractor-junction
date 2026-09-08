<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Engagement\Services\ReviewService;
use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function index(Request $request): View
    {
        return view('admin.reviews.index', [
            'reviews' => Review::with(['user', 'reviewable', 'aspectRatings'])
                ->where('status', $request->query('status', 'pending'))
                ->latest()->paginate(20)->withQueryString(),
            'status' => $request->query('status', 'pending'),
            'counts' => [
                'pending' => Review::where('status', 'pending')->count(),
                'approved' => Review::where('status', 'approved')->count(),
                'rejected' => Review::where('status', 'rejected')->count(),
            ],
        ]);
    }

    public function moderate(Request $request, Review $review): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'reason' => ['required_if:status,rejected', 'nullable', 'string', 'max:500'],
        ], [
            'reason.required_if' => __('A reason is required — the author sees it.'),
        ]);

        DB::transaction(function () use ($review, $data, $request) {
            $review->update([
                'status' => $data['status'],
                'rejection_reason' => $data['reason'] ?? null,
                'moderated_by' => $request->user()->id,
            ]);

            if ($review->reviewable) {
                $this->reviews->recalculateAggregate($review->reviewable);
            }
        });

        return response()->json([
            'status' => 'ok',
            'message' => $data['status'] === 'approved' ? __('Review published.') : __('Review rejected.'),
        ]);
    }
}
