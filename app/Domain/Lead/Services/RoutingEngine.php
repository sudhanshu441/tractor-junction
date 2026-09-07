<?php

namespace App\Domain\Lead\Services;

use App\Models\Dealer;
use App\Models\Lead;
use App\Models\LeadAssignment;
use App\Models\Product;
use App\Models\RoutingRule;
use App\Models\UsedListing;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Decides who works a lead.
 *
 * Ordered rules, first match wins; the winning rule id is stored on the
 * assignment so any routing decision can be explained after the fact rather
 * than guessed at from logs.
 */
class RoutingEngine
{
    /**
     * @return LeadAssignment|null null when nothing could take it (the caller alerts)
     */
    public function route(Lead $lead): ?LeadAssignment
    {
        // A lead about someone's own listing belongs to that seller, always.
        if ($lead->type === 'used_listing' && $lead->leadable) {
            return $this->assignToListingOwner($lead);
        }

        $rule = $this->matchRule($lead);

        if (! $rule) {
            return $this->assignFallback($lead, null);
        }

        return match ($rule->assignee_type) {
            'dealer' => $this->assignToDealer($lead, $rule) ?? $this->assignFallback($lead, $rule),
            'staff' => $this->assignToStaff($lead, $rule) ?? $this->assignFallback($lead, $rule),
            default => $this->assignFallback($lead, $rule),
        };
    }

    public function matchRule(Lead $lead): ?RoutingRule
    {
        $brandId = $this->brandIdFor($lead);
        $categoryId = $this->categoryIdFor($lead);

        return RoutingRule::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('lead_type')->orWhere('lead_type', $lead->type))
            ->where(fn ($q) => $q->whereNull('state_id')->orWhere('state_id', $lead->state_id))
            ->where(fn ($q) => $q->whereNull('district_id')->orWhere('district_id', $lead->district_id))
            ->where(fn ($q) => $q->whereNull('brand_id')->orWhere('brand_id', $brandId))
            ->where(fn ($q) => $q->whereNull('category_id')->orWhere('category_id', $categoryId))
            ->orderBy('priority')
            ->orderByDesc('district_id')   // a district rule beats a state-wide one
            ->first();
    }

    /**
     * Eligible dealers ranked by plan tier, then responsiveness, then who has
     * had the fewest leads today — so volume spreads and slow dealers slide.
     */
    public function eligibleDealers(Lead $lead, RoutingRule $rule): Collection
    {
        $brandId = $this->brandIdFor($lead);

        return Dealer::query()
            ->verified()
            ->when($lead->district_id, fn ($q) => $q->where('district_id', $lead->district_id))
            ->when(! $lead->district_id && $lead->state_id, fn ($q) => $q->where('state_id', $lead->state_id))
            ->when($brandId, fn ($q) => $q->whereHas('brands', fn ($b) => $b->where('brands.id', $brandId)))
            ->with(['activeSubscription.plan'])
            ->withCount(['leadAssignments as leads_today' => fn ($q) => $q->whereDate('assigned_at', today())])
            ->get()
            ->filter(fn (Dealer $dealer) => $this->withinCap($dealer, $rule))
            ->sortBy([
                fn (Dealer $a, Dealer $b) => $this->planRank($b) <=> $this->planRank($a),
                fn (Dealer $a, Dealer $b) => $b->response_score <=> $a->response_score,
                fn (Dealer $a, Dealer $b) => $a->leads_today <=> $b->leads_today,
            ])
            ->values();
    }

    private function assignToDealer(Lead $lead, RoutingRule $rule): ?LeadAssignment
    {
        $dealer = $this->eligibleDealers($lead, $rule)->first();

        if (! $dealer) {
            return null;
        }

        return $this->createAssignment($lead, [
            'assignee_type' => 'dealer',
            'dealer_id' => $dealer->id,
            'routing_rule_id' => $rule->id,
        ]);
    }

    private function assignToStaff(Lead $lead, RoutingRule $rule): ?LeadAssignment
    {
        $staff = User::staff()->active()
            ->when($lead->state_id, fn ($q) => $q->where('state_id', $lead->state_id))
            ->withCount(['leadAssignments as leads_today' => fn ($q) => $q->whereDate('assigned_at', today())])
            ->orderBy('leads_today')
            ->first();

        if (! $staff) {
            return null;
        }

        return $this->createAssignment($lead, [
            'assignee_type' => 'staff',
            'user_id' => $staff->id,
            'routing_rule_id' => $rule->id,
        ]);
    }

    private function assignToListingOwner(Lead $lead): ?LeadAssignment
    {
        $listing = $lead->leadable;
        $ownerId = $listing->user_id ?? null;

        if (! $ownerId && $listing->dealer_id) {
            return $this->createAssignment($lead, [
                'assignee_type' => 'dealer',
                'dealer_id' => $listing->dealer_id,
            ]);
        }

        if (! $ownerId) {
            return null;
        }

        return $this->createAssignment($lead, [
            'assignee_type' => 'seller',
            'user_id' => $ownerId,
        ]);
    }

    private function assignFallback(Lead $lead, ?RoutingRule $rule): ?LeadAssignment
    {
        $fallbackId = $rule?->fallback_user_id;

        $user = $fallbackId
            ? User::find($fallbackId)
            : User::staff()->active()->when($lead->state_id, fn ($q) => $q->where('state_id', $lead->state_id))->first();

        if (! $user) {
            return null;
        }

        return $this->createAssignment($lead, [
            'assignee_type' => 'staff',
            'user_id' => $user->id,
            'routing_rule_id' => $rule?->id,
        ]);
    }

    private function createAssignment(Lead $lead, array $attributes): LeadAssignment
    {
        return DB::transaction(function () use ($lead, $attributes) {
            $assignment = LeadAssignment::create([
                ...$attributes,
                'lead_id' => $lead->id,
                'status' => 'pending',
                'assigned_at' => now(),
            ]);

            $lead->forceFill(['status' => 'assigned'])->save();

            return $assignment;
        });
    }

    private function withinCap(Dealer $dealer, RoutingRule $rule): bool
    {
        $cap = $rule->daily_cap ?: ($dealer->activeSubscription?->plan?->daily_lead_cap ?? 0);

        return $cap === 0 || $dealer->leads_today < $cap;
    }

    private function planRank(Dealer $dealer): int
    {
        return match ($dealer->activeSubscription?->plan?->slug) {
            'gold' => 3,
            'silver' => 2,
            'free' => 1,
            default => 0,
        };
    }

    private function brandIdFor(Lead $lead): ?int
    {
        return match (true) {
            $lead->leadable instanceof Product => $lead->leadable->brand_id,
            $lead->leadable instanceof UsedListing => $lead->leadable->brand_id,
            default => null,
        };
    }

    private function categoryIdFor(Lead $lead): ?int
    {
        return match (true) {
            $lead->leadable instanceof Product => $lead->leadable->category_id,
            $lead->leadable instanceof UsedListing => $lead->leadable->category_id,
            default => null,
        };
    }
}
