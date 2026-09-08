<?php

namespace App\Http\Controllers\Web;

use App\Domain\Engagement\Services\ReviewService;
use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Owner reviews written from the model and dealer pages.
 *
 * Both endpoints require a signed-in account: a review carries a person's name
 * on a public page, and a helpful vote has to be attributable to stay honest.
 */
class ReviewController extends Controller
{
    public function __construct(private readonly ReviewService $reviews) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject_type' => ['required', 'in:product,dealer'],
            'subject_id' => ['required', 'integer'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'min:20', 'max:3000'],
            'ownership_duration' => ['nullable', 'in:'.implode(',', array_keys(ReviewService::OWNERSHIP_DURATIONS))],
            'pros' => ['nullable', 'string', 'max:500'],
            'cons' => ['nullable', 'string', 'max:500'],
            'aspects' => ['nullable', 'array'],
            'aspects.*' => ['nullable', 'integer', 'min:1', 'max:5'],
        ], [
            'body.min' => __('Please write at least a couple of sentences — one word helps nobody.'),
        ]);

        $subject = $this->resolveSubject($data['subject_type'], (int) $data['subject_id']);

        if (! $subject) {
            return response()->json(['status' => 'error', 'message' => __('That page no longer exists.')], 404);
        }

        if ($this->alreadyReviewed($subject, $request)) {
            return response()->json([
                'status' => 'error',
                'message' => __('You have already reviewed this. Edit requests go to our team.'),
            ], 422);
        }

        $this->reviews->submit($subject, $request->user(), $data, $data['aspects'] ?? []);

        return response()->json([
            'status' => 'ok',
            'message' => __('Thank you. Your review goes live once our team has read it.'),
        ]);
    }

    public function vote(Request $request, Review $review): JsonResponse
    {
        abort_unless($review->status === 'approved', 404);

        $data = $request->validate(['helpful' => ['required', 'boolean']]);

        $counts = $this->reviews->vote($review, $request->user(), (bool) $data['helpful']);

        return response()->json([
            'status' => 'ok',
            'message' => __('Thanks for the feedback.'),
            'data' => $counts,
        ]);
    }

    private function resolveSubject(string $type, int $id): ?Model
    {
        return match ($type) {
            'product' => Product::where('is_active', true)->find($id),
            'dealer' => Dealer::where('verification_status', 'verified')->find($id),
            default => null,
        };
    }

    private function alreadyReviewed(Model $subject, Request $request): bool
    {
        return Review::where('reviewable_type', $subject->getMorphClass())
            ->where('reviewable_id', $subject->getKey())
            ->where('user_id', $request->user()->id)
            ->exists();
    }
}
