<?php

namespace App\Domain\Engagement\Services;

use App\Models\Dealer;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Owner reviews for models and dealers.
 *
 * Everything lands in `pending`: a review is published by a moderator, never by
 * the author. The star average is cached on the subject so a listing page never
 * has to aggregate a review table per row.
 */
class ReviewService
{
    /** Aspects a buyer can score, by what is being reviewed. */
    public const ASPECTS = [
        'product' => ['mileage', 'comfort', 'maintenance', 'performance', 'value'],
        'dealer' => ['service', 'value'],
    ];

    public const OWNERSHIP_DURATIONS = [
        'under_6_months' => 'Less than 6 months',
        '6_to_12_months' => '6 to 12 months',
        '1_to_3_years' => '1 to 3 years',
        'over_3_years' => 'More than 3 years',
    ];

    /** @param  array<string, int>  $aspects */
    public function submit(Model $subject, User $author, array $data, array $aspects = []): Review
    {
        return DB::transaction(function () use ($subject, $author, $data, $aspects) {
            $review = $subject->reviews()->create([
                'user_id' => $author->id,
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'rating' => (int) $data['rating'],
                'ownership_duration' => $data['ownership_duration'] ?? null,
                'pros' => $data['pros'] ?? null,
                'cons' => $data['cons'] ?? null,
                'is_verified_owner' => $this->isVerifiedOwner($subject, $author),
                'status' => 'pending',
            ]);

            $allowed = self::ASPECTS[$this->kind($subject)] ?? [];

            foreach ($aspects as $aspect => $rating) {
                if (! in_array($aspect, $allowed, true) || ! $rating) {
                    continue;
                }

                $review->aspectRatings()->create([
                    'aspect' => $aspect,
                    'rating' => max(1, min(5, (int) $rating)),
                ]);
            }

            return $review->refresh();
        });
    }

    /**
     * One vote per person per review, and changing your mind flips the counts
     * rather than adding a second vote.
     */
    public function vote(Review $review, User $voter, bool $helpful): array
    {
        DB::transaction(function () use ($review, $voter, $helpful) {
            $existing = ReviewVote::where('review_id', $review->id)->where('user_id', $voter->id)->first();

            if ($existing && (bool) $existing->is_helpful === $helpful) {
                return;
            }

            if ($existing) {
                $existing->forceFill(['is_helpful' => $helpful])->save();
            } else {
                ReviewVote::create([
                    'review_id' => $review->id,
                    'user_id' => $voter->id,
                    'is_helpful' => $helpful,
                ]);
            }

            $review->forceFill([
                'helpful_count' => ReviewVote::where('review_id', $review->id)->where('is_helpful', true)->count(),
                'unhelpful_count' => ReviewVote::where('review_id', $review->id)->where('is_helpful', false)->count(),
            ])->save();
        });

        $review->refresh();

        return ['helpful' => $review->helpful_count, 'unhelpful' => $review->unhelpful_count];
    }

    /** Recomputes the cached average from approved reviews only. */
    public function recalculateAggregate(Model $subject): void
    {
        $stats = Review::where('reviewable_type', $subject->getMorphClass())
            ->where('reviewable_id', $subject->getKey())
            ->where('status', 'approved')
            ->selectRaw('avg(rating) as average, count(*) as total')
            ->first();

        $subject->forceFill([
            'rating_avg' => round((float) ($stats->average ?? 0), 2),
            'rating_count' => (int) ($stats->total ?? 0),
        ])->save();
    }

    /**
     * The distribution and per-aspect averages a model page shows beside the
     * headline star rating.
     *
     * Returned as plain arrays: these are cached, and a serialised Eloquent
     * collection comes back as __PHP_Incomplete_Class on a warm cache.
     *
     * @return array{total: int, average: float, distribution: array<int, int>, aspects: array<string, float>}
     */
    public function summary(Model $subject): array
    {
        $rows = Review::where('reviewable_type', $subject->getMorphClass())
            ->where('reviewable_id', $subject->getKey())
            ->where('status', 'approved')
            ->selectRaw('rating, count(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating')
            ->all();

        $distribution = [];
        $sum = 0;
        $count = 0;

        foreach ([5, 4, 3, 2, 1] as $star) {
            $distribution[$star] = (int) ($rows[$star] ?? 0);
            $sum += $star * $distribution[$star];
            $count += $distribution[$star];
        }

        $aspects = DB::table('review_ratings')
            ->join('reviews', 'reviews.id', '=', 'review_ratings.review_id')
            ->where('reviews.reviewable_type', $subject->getMorphClass())
            ->where('reviews.reviewable_id', $subject->getKey())
            ->where('reviews.status', 'approved')
            ->groupBy('review_ratings.aspect')
            ->selectRaw('review_ratings.aspect, avg(review_ratings.rating) as average')
            ->pluck('average', 'aspect')
            ->map(fn ($value) => round((float) $value, 1))
            ->all();

        return [
            'total' => $count,
            'average' => $count > 0 ? round($sum / $count, 1) : 0.0,
            'distribution' => $distribution,
            'aspects' => $aspects,
        ];
    }

    /**
     * "Verified owner" is a claim we can support: this person enquired about
     * this machine or dealer through us. It is not proof of purchase, so the
     * badge says owner, not buyer, and moderation still applies.
     */
    private function isVerifiedOwner(Model $subject, User $author): bool
    {
        return Lead::where('user_id', $author->id)
            ->where('leadable_type', $subject->getMorphClass())
            ->where('leadable_id', $subject->getKey())
            ->exists();
    }

    public function kind(Model $subject): string
    {
        return match (true) {
            $subject instanceof Dealer => 'dealer',
            $subject instanceof Product => 'product',
            default => 'product',
        };
    }
}
