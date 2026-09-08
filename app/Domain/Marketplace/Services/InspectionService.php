<?php

namespace App\Domain\Marketplace\Services;

use App\Domain\Notification\NotificationDispatcher;
use App\Models\Inspection;
use App\Models\InspectionChecklistItem;
use App\Models\UsedListing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Physical inspection of a used machine.
 *
 *   requested → scheduled → in_progress → completed | cancelled
 *
 * A completed, approved inspection is what earns a listing its verified badge,
 * so the score has to come from the checklist rather than an inspector's
 * overall impression.
 */
class InspectionService
{
    public const TRANSITIONS = [
        'requested' => ['scheduled', 'cancelled'],
        'scheduled' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => ['requested'],
    ];

    /** Grade boundaries on the weighted 0-100 score. */
    public const GRADES = ['A' => 85, 'B' => 70, 'C' => 50, 'D' => 0];

    public function __construct(
        private readonly ValuationService $valuation,
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function nextReference(): string
    {
        $last = Inspection::max('id') ?? 0;

        return 'KJ-I-'.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }

    public function request(UsedListing $listing): Inspection
    {
        // Queried on the model rather than through the listing's latestOfMany
        // relation, whose subquery join makes an unqualified where ambiguous.
        return Inspection::firstOrCreate(
            ['used_listing_id' => $listing->id],
            ['reference_no' => $this->nextReference(), 'status' => 'requested'],
        );
    }

    public function schedule(Inspection $inspection, int $inspectorId, string $scheduledAt): Inspection
    {
        $inspection->forceFill([
            'inspector_id' => $inspectorId,
            'scheduled_at' => $scheduledAt,
        ])->save();

        $inspection = $this->changeStatus($inspection, 'scheduled');

        $this->notifications->send('inspection.scheduled', $inspection->listing?->seller, [
            'reference' => $inspection->listing?->reference_no,
            'date' => $inspection->scheduled_at?->format('d M Y'),
        ]);

        return $inspection;
    }

    /**
     * Records the inspector's scores and derives the overall grade and a
     * valuation band from them.
     *
     * @param  array<int, array{score: int, remarks?: string}>  $scores  keyed by checklist item id
     */
    public function complete(Inspection $inspection, array $scores, ?string $summary = null): Inspection
    {
        return DB::transaction(function () use ($inspection, $scores, $summary) {
            $items = InspectionChecklistItem::whereIn('id', array_keys($scores))->get()->keyBy('id');

            $weighted = 0.0;
            $totalWeight = 0.0;

            foreach ($scores as $itemId => $entry) {
                $item = $items->get((int) $itemId);

                if (! $item) {
                    continue;
                }

                $score = max(0, min(10, (int) ($entry['score'] ?? 0)));

                $inspection->items()->updateOrCreate(
                    ['inspection_checklist_item_id' => $item->id],
                    ['score' => $score, 'remarks' => $entry['remarks'] ?? null],
                );

                $weighted += $score * (float) $item->weight;
                $totalWeight += 10 * (float) $item->weight;
            }

            $overall = $totalWeight > 0 ? round($weighted / $totalWeight * 100, 2) : null;

            $inspection->forceFill([
                'overall_score' => $overall,
                'grade' => $overall !== null ? $this->gradeFor($overall) : null,
                'summary' => $summary,
                'completed_at' => now(),
            ])->save();

            $this->applyValuation($inspection);

            return $this->changeStatus($inspection, 'completed');
        });
    }

    public function gradeFor(float $score): string
    {
        foreach (self::GRADES as $grade => $floor) {
            if ($score >= $floor) {
                return $grade;
            }
        }

        return 'D';
    }

    /**
     * An approved report is what flips the verified badge — never the
     * inspector's own submission, so a bad report cannot self-publish.
     */
    public function approve(Inspection $inspection): Inspection
    {
        if ($inspection->status !== 'completed') {
            throw new \InvalidArgumentException('Only a completed inspection can be approved.');
        }

        return DB::transaction(function () use ($inspection) {
            $inspection->forceFill(['approved_by' => Auth::id()])->save();

            $listing = $inspection->listing;

            if ($listing) {
                $listing->forceFill(['is_verified' => true])->save();

                $this->notifications->send('inspection.completed', $listing->seller, [
                    'reference' => $listing->reference_no,
                    'grade' => $inspection->grade,
                ]);
            }

            return $inspection->refresh();
        });
    }

    /** @throws \InvalidArgumentException on an illegal transition */
    public function changeStatus(Inspection $inspection, string $to): Inspection
    {
        $from = $inspection->status;

        if ($from !== $to && ! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new \InvalidArgumentException("Cannot move an inspection from {$from} to {$to}.");
        }

        $inspection->forceFill(['status' => $to])->save();

        return $inspection->refresh();
    }

    /** The inspected grade replaces the seller's self-declared condition. */
    private function applyValuation(Inspection $inspection): void
    {
        $listing = $inspection->listing;

        if (! $listing) {
            return;
        }

        $condition = match ($inspection->grade) {
            'A' => 'excellent',
            'B' => 'good',
            'C' => 'average',
            default => 'needs_repair',
        };

        $estimate = $this->valuation->estimate(
            tap($listing->replicate(), fn ($copy) => $copy->condition = $condition)
                ->setRelation('state', $listing->state),
        );

        if ($estimate) {
            $inspection->forceFill([
                'valuation_min' => $estimate['min'],
                'valuation_max' => $estimate['max'],
            ])->save();
        }
    }
}
