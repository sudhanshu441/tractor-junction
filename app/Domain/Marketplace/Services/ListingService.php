<?php

namespace App\Domain\Marketplace\Services;

use App\Domain\Notification\NotificationDispatcher;
use App\Models\UsedListing;
use App\Models\UsedListingStatusLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Owns the used-listing state machine.
 *
 *   draft → pending → live → sold | expired | rejected | blocked
 *
 * Every transition goes through transition(), so no status can change without
 * an audit row and the matching notification.
 */
class ListingService
{
    /** from => allowed destinations */
    public const TRANSITIONS = [
        'draft' => ['pending'],
        'pending' => ['live', 'rejected', 'blocked', 'draft'],
        'live' => ['sold', 'expired', 'blocked', 'pending'],
        'rejected' => ['pending', 'blocked'],
        'expired' => ['pending', 'live'],
        'sold' => [],
        'blocked' => ['pending'],
    ];

    public function __construct(
        private readonly ValuationService $valuation,
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function nextReference(): string
    {
        $last = UsedListing::withTrashed()->max('id') ?? 0;

        return 'KJ-U-'.str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }

    /** Creates or updates the seller's draft; each wizard step calls this. */
    public function saveDraft(array $data, ?UsedListing $listing = null, ?int $userId = null): UsedListing
    {
        return DB::transaction(function () use ($data, $listing, $userId) {
            if ($listing) {
                $listing->update($data);

                return $listing->refresh();
            }

            return UsedListing::create([
                ...$data,
                'reference_no' => $this->nextReference(),
                'user_id' => $userId,
                'status' => 'draft',
                'seller_type' => $data['seller_type'] ?? 'owner',
            ]);
        });
    }

    /** Seller submits: draft → pending, slug fixed, moderation notified. */
    public function submit(UsedListing $listing): UsedListing
    {
        // Draft columns are nullable; nothing reaches moderation without them.
        foreach (['category_id', 'brand_id', 'manufacturing_year', 'expected_price', 'state_id'] as $required) {
            if (blank($listing->{$required})) {
                throw new \InvalidArgumentException("A listing cannot be submitted without {$required}.");
            }
        }

        $listing->forceFill([
            'title' => $listing->title ?: $this->buildTitle($listing),
            'slug' => $listing->slug ?: $this->uniqueSlug($listing),
        ])->save();

        return $this->transition($listing, 'pending', __('Submitted by seller'));
    }

    public function approve(UsedListing $listing, ?string $remarks = null): UsedListing
    {
        $listing->forceFill([
            'published_at' => now(),
            'expires_at' => now()->addDays((int) config('kj.listings.expiry_days')),
            'approved_by' => Auth::id(),
            'rejection_reason' => null,
        ])->save();

        return $this->transition($listing, 'live', $remarks ?? __('Approved'));
    }

    public function reject(UsedListing $listing, string $reason): UsedListing
    {
        $listing->forceFill(['rejection_reason' => $reason])->save();

        return $this->transition($listing, 'rejected', $reason);
    }

    public function requestChanges(UsedListing $listing, string $reason): UsedListing
    {
        // Stays pending — the seller edits and it returns to the same queue.
        $listing->forceFill(['rejection_reason' => $reason])->save();

        $this->log($listing, $listing->status, 'pending', $reason);
        $this->notifications->send('listing.rejected', $listing->seller, [
            'reference' => $listing->reference_no,
            'reason' => $reason,
        ]);

        return $listing;
    }

    public function block(UsedListing $listing, string $reason): UsedListing
    {
        $listing->forceFill(['rejection_reason' => $reason])->save();

        return $this->transition($listing, 'blocked', $reason);
    }

    public function markSold(UsedListing $listing): UsedListing
    {
        $listing->forceFill(['sold_at' => now()])->save();

        return $this->transition($listing, 'sold', __('Marked sold by seller'));
    }

    public function expire(UsedListing $listing): UsedListing
    {
        return $this->transition($listing, 'expired', __('Expired automatically'));
    }

    /** Renew restarts the clock without a second moderation pass. */
    public function renew(UsedListing $listing): UsedListing
    {
        $listing->forceFill([
            'expires_at' => now()->addDays((int) config('kj.listings.expiry_days')),
            'published_at' => $listing->published_at ?? now(),
        ])->save();

        if ($listing->status === 'expired') {
            return $this->transition($listing, 'live', __('Renewed by seller'));
        }

        $this->log($listing, $listing->status, $listing->status, __('Renewed by seller'));

        return $listing;
    }

    /**
     * The one place a listing's status changes.
     *
     * @throws \InvalidArgumentException on an illegal transition
     */
    public function transition(UsedListing $listing, string $to, ?string $remarks = null): UsedListing
    {
        $from = $listing->status;

        if ($from !== $to && ! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new \InvalidArgumentException("Cannot move a listing from {$from} to {$to}.");
        }

        return DB::transaction(function () use ($listing, $from, $to, $remarks) {
            $listing->forceFill(['status' => $to])->save();

            $this->log($listing, $from, $to, $remarks);
            $this->notifyTransition($listing, $to, $remarks);

            return $listing->refresh();
        });
    }

    private function log(UsedListing $listing, ?string $from, string $to, ?string $remarks): void
    {
        UsedListingStatusLog::create([
            'used_listing_id' => $listing->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => Auth::id(),
            'remarks' => $remarks,
        ]);
    }

    private function notifyTransition(UsedListing $listing, string $to, ?string $remarks): void
    {
        $seller = $listing->seller;

        if (! $seller) {
            return;
        }

        $payload = ['reference' => $listing->reference_no, 'reason' => $remarks ?? ''];

        match ($to) {
            'pending' => $this->notifications->send('listing.submitted', $seller, [
                ...$payload, 'hours' => config('kj.listings.moderation_sla_hours'),
            ]),
            'live' => $this->notifications->send('listing.approved', $seller, [
                ...$payload, 'url' => route('used.show', $listing->slug),
            ]),
            'rejected', 'blocked' => $this->notifications->send('listing.rejected', $seller, $payload),
            'expired' => $this->notifications->send('listing.expired', $seller, $payload),
            'sold' => $this->notifications->send('listing.sold', $seller, $payload),
            default => null,
        };
    }

    /**
     * A seller posting the same machine twice usually means they lost the first
     * one, not that they have two. Surfacing it beats silently accepting both.
     */
    public function findRecentDuplicate(string $mobile, ?int $productId, ?int $brandId, ?int $excludeId = null): ?UsedListing
    {
        return UsedListing::query()
            ->whereHas('seller', fn ($q) => $q->where('mobile', $mobile))
            ->whereIn('status', ['pending', 'live'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->when($productId, fn ($q) => $q->where('product_id', $productId),
                fn ($q) => $q->when($brandId, fn ($q2) => $q2->where('brand_id', $brandId)))
            ->where('created_at', '>=', now()->subDay())
            ->first();
    }

    public function buildTitle(UsedListing $listing): string
    {
        return trim(collect([
            $listing->brand?->name,
            $listing->product?->name,
            $listing->manufacturing_year,
        ])->filter()->implode(' '));
    }

    private function uniqueSlug(UsedListing $listing): string
    {
        $base = Str::slug($this->buildTitle($listing).' '.($listing->city?->name ?? ''));

        return trim($base, '-').'-'.Str::lower(Str::afterLast($listing->reference_no, '-'));
    }
}
