<?php

namespace App\Domain\Lead\Services;

use App\Domain\Notification\NotificationDispatcher;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Captures, dedupes and progresses every enquiry in the product.
 *
 * All nine enquiry types share one table and one lifecycle:
 *   new → assigned → contacted → qualified → converted | lost
 * plus the terminal side-branches duplicate and invalid.
 */
class LeadService
{
    public const OPEN_STATUSES = ['new', 'assigned', 'contacted', 'qualified'];

    /** from => allowed destinations */
    public const TRANSITIONS = [
        'new' => ['assigned', 'contacted', 'duplicate', 'invalid', 'lost'],
        'assigned' => ['contacted', 'assigned', 'lost', 'invalid'],
        'contacted' => ['qualified', 'lost', 'invalid', 'converted'],
        'qualified' => ['converted', 'lost'],
        'converted' => [],
        'lost' => ['contacted'],
        'duplicate' => [],
        'invalid' => [],
    ];

    public function __construct(
        private readonly RoutingEngine $routing,
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function nextReference(): string
    {
        $last = Lead::withTrashed()->max('id') ?? 0;

        return 'KJ-L-'.str_pad((string) ($last + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Capture an enquiry and route it.
     *
     * @param  Model|null  $about  Product | UsedListing | Dealer | Offer
     */
    public function capture(string $type, array $data, ?Model $about = null, ?User $user = null): Lead
    {
        return DB::transaction(function () use ($type, $data, $about, $user) {
            $duplicate = $this->findDuplicate($data['mobile'], $about);

            $lead = Lead::create([
                'reference_no' => $this->nextReference(),
                'type' => $type,
                'leadable_type' => $about?->getMorphClass(),
                'leadable_id' => $about?->getKey(),
                'user_id' => $user?->id,
                'name' => $data['name'],
                'mobile' => $data['mobile'],
                'mobile_verified' => $data['mobile_verified'] ?? false,
                'email' => $data['email'] ?? null,
                'state_id' => $data['state_id'] ?? $user?->state_id,
                'district_id' => $data['district_id'] ?? $user?->district_id,
                'city_id' => $data['city_id'] ?? $user?->city_id,
                'message' => $data['message'] ?? null,
                'meta' => $data['meta'] ?? null,
                'lead_source_id' => $this->sourceId($data['utm_source'] ?? null),
                'channel' => $data['channel'] ?? 'web',
                'status' => $duplicate ? 'duplicate' : 'new',
                'duplicate_of_id' => $duplicate?->id,
                'ip' => $data['ip'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);

            if ($duplicate) {
                // Keep the original moving instead of opening a second thread.
                $this->addActivity($duplicate, 'note',
                    __('Buyer :name enquired again (:reference).', ['name' => $lead->name, 'reference' => $lead->reference_no]));

                return $lead;
            }

            $this->afterCapture($lead, $about);

            return $lead->refresh();
        });
    }

    private function afterCapture(Lead $lead, ?Model $about): void
    {
        $assignment = $this->routing->route($lead->load('leadable'));

        $this->notifications->send('lead.created.buyer', $lead->user ?? $this->pseudoUser($lead), [
            'name' => $lead->name,
            'reference' => $lead->reference_no,
        ]);

        if (! $assignment) {
            $this->addActivity($lead, 'note', __('No assignee matched — needs manual routing.'));

            return;
        }

        $this->addActivity($lead, 'assignment', __('Assigned automatically'), null, 'assigned');

        if ($assignment->assignee_type === 'dealer' && $assignment->dealer?->owner) {
            $this->notifications->send('lead.assigned.dealer', $assignment->dealer->owner, [
                'reference' => $lead->reference_no,
                'district' => $lead->district?->name ?? '—',
                'sla' => config('kj.leads.response_sla_minutes'),
            ]);
        }

        if (in_array($assignment->assignee_type, ['seller', 'staff'], true) && $assignment->user) {
            $this->notifications->send(
                $assignment->assignee_type === 'seller' ? 'lead.assigned.seller' : 'lead.assigned.dealer',
                $assignment->user,
                [
                    'reference' => $lead->reference_no,
                    'district' => $lead->district?->name ?? '—',
                    'sla' => config('kj.leads.response_sla_minutes'),
                ],
            );
        }
    }

    /**
     * The same person asking about the same thing inside the dedupe window is
     * one lead, not two — otherwise dealers get paid-for duplicates.
     */
    public function findDuplicate(string $mobile, ?Model $about): ?Lead
    {
        return Lead::query()
            ->where('mobile', $mobile)
            ->whereIn('status', self::OPEN_STATUSES)
            ->when($about,
                fn ($q) => $q->where('leadable_type', $about->getMorphClass())->where('leadable_id', $about->getKey()),
                fn ($q) => $q->whereNull('leadable_id'))
            ->where('created_at', '>=', now()->subDays((int) config('kj.leads.duplicate_window_days')))
            ->first();
    }

    /** @throws \InvalidArgumentException on an illegal transition */
    public function changeStatus(Lead $lead, string $to, ?string $reason = null): Lead
    {
        $from = $lead->status;

        if ($from !== $to && ! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new \InvalidArgumentException("Cannot move a lead from {$from} to {$to}.");
        }

        return DB::transaction(function () use ($lead, $from, $to, $reason) {
            $lead->forceFill([
                'status' => $to,
                'lost_reason' => $to === 'lost' ? $reason : $lead->lost_reason,
                'first_contacted_at' => $to === 'contacted' ? ($lead->first_contacted_at ?? now()) : $lead->first_contacted_at,
                'converted_at' => $to === 'converted' ? now() : $lead->converted_at,
            ])->save();

            $this->addActivity($lead, 'status_change', $reason, $from, $to);

            if ($to === 'contacted') {
                $this->recordResponsiveness($lead);
            }

            return $lead->refresh();
        });
    }

    public function addActivity(Lead $lead, string $activity, ?string $description = null, ?string $from = null, ?string $to = null): LeadActivity
    {
        return LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => Auth::id(),
            'activity' => $activity,
            'from_status' => $from,
            'to_status' => $to,
            'description' => $description,
        ]);
    }

    /** Merge a duplicate into the lead it repeats. */
    public function merge(Lead $duplicate, Lead $original): void
    {
        DB::transaction(function () use ($duplicate, $original) {
            $duplicate->forceFill(['status' => 'duplicate', 'duplicate_of_id' => $original->id])->save();

            $this->addActivity($original, 'note',
                __('Merged duplicate :reference.', ['reference' => $duplicate->reference_no]));
        });
    }

    /**
     * A dealer's response time drives their routing priority, so it is recorded
     * the moment they first make contact.
     */
    private function recordResponsiveness(Lead $lead): void
    {
        $assignment = $lead->currentAssignment;

        if (! $assignment?->dealer_id) {
            return;
        }

        $assignment->forceFill(['status' => 'accepted', 'responded_at' => now()])->save();

        $minutes = $assignment->assigned_at->diffInMinutes(now());
        $sla = (int) config('kj.leads.response_sla_minutes');

        $dealer = $assignment->dealer;
        $delta = $minutes <= $sla ? 0.1 : -0.2;

        $dealer->forceFill([
            'response_score' => max(0, min(10, (float) $dealer->response_score + $delta)),
        ])->save();
    }

    private function sourceId(?string $utmSource): ?int
    {
        $slug = $utmSource ? str($utmSource)->slug()->toString() : 'organic';

        return LeadSource::where('slug', $slug)->value('id')
            ?? LeadSource::where('slug', 'organic')->value('id');
    }

    /** Guests get notified by mobile without an account being created for them. */
    private function pseudoUser(Lead $lead): User
    {
        $user = new User;
        $user->forceFill(['name' => $lead->name, 'mobile' => $lead->mobile]);

        return $user;
    }
}
