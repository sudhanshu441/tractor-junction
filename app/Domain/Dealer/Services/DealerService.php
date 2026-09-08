<?php

namespace App\Domain\Dealer\Services;

use App\Domain\Notification\NotificationDispatcher;
use App\Models\Dealer;
use App\Models\DealerDocument;
use App\Models\Plan;
use App\Models\User;
use App\Support\DocumentVault;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dealer onboarding and verification.
 *
 *   pending → verified | rejected, verified ⇄ suspended
 *
 * Verification is what unlocks lead routing, so it is the gate everything
 * else hangs off.
 */
class DealerService
{
    public const TRANSITIONS = [
        'pending' => ['verified', 'rejected'],
        'verified' => ['suspended'],
        'suspended' => ['verified'],
        'rejected' => ['pending'],
    ];

    public function __construct(
        private readonly DocumentVault $vault,
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function nextCode(): string
    {
        $last = Dealer::withTrashed()->max('id') ?? 0;

        return 'KJ-D-'.str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }

    /** Public "become a dealer" registration. */
    public function register(array $data, array $brandIds = [], ?User $owner = null): Dealer
    {
        return DB::transaction(function () use ($data, $brandIds, $owner) {
            $dealer = Dealer::create([
                ...$data,
                'code' => $this->nextCode(),
                'slug' => $this->uniqueSlug($data['display_name'] ?? $data['business_name']),
                'owner_user_id' => $owner?->id,
                'verification_status' => 'pending',
                'is_active' => true,
            ]);

            if ($brandIds) {
                $dealer->brands()->sync($brandIds);
            }

            if ($owner) {
                // The owner becomes a dealer-type user so the panel gate lets them in.
                $owner->forceFill(['user_type' => 'dealer'])->save();

                if (! $owner->hasRole('dealer-owner')) {
                    $owner->assignRole('dealer-owner');
                }

                $dealer->staff()->firstOrCreate(
                    ['user_id' => $owner->id],
                    ['role' => 'owner', 'is_active' => true],
                );
            }

            $this->startFreePlan($dealer);

            $this->notifications->send('dealer.registered', $owner, [
                'business' => $dealer->display_name,
            ]);

            return $dealer->refresh();
        });
    }

    public function attachDocument(Dealer $dealer, UploadedFile $file, string $type): DealerDocument
    {
        $path = $this->vault->store($file, 'dealer-documents/'.$dealer->code);

        return $dealer->documents()->updateOrCreate(
            ['doc_type' => $type],
            [
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'status' => 'pending',
                'remarks' => null,
                'verified_by' => null,
            ],
        );
    }

    /** @throws \InvalidArgumentException on an illegal transition */
    public function changeStatus(Dealer $dealer, string $to, ?string $remarks = null): Dealer
    {
        $from = $dealer->verification_status;

        if ($from !== $to && ! in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new \InvalidArgumentException("Cannot move a dealer from {$from} to {$to}.");
        }

        return DB::transaction(function () use ($dealer, $to, $remarks) {
            $dealer->forceFill([
                'verification_status' => $to,
                'verification_remarks' => $remarks,
                'verified_by' => $to === 'verified' ? Auth::id() : $dealer->verified_by,
                'verified_at' => $to === 'verified' ? now() : $dealer->verified_at,
            ])->save();

            activity()->performedOn($dealer)->causedBy(Auth::user())
                ->withProperties(['status' => $to, 'remarks' => $remarks])
                ->log('Dealer verification updated');

            $owner = $dealer->owner;

            if ($owner) {
                match ($to) {
                    'verified' => $this->notifications->send('dealer.verified', $owner, ['code' => $dealer->code]),
                    'rejected', 'suspended' => $this->notifications->send('dealer.rejected', $owner, [
                        'reason' => $remarks ?? '',
                    ]),
                    default => null,
                };
            }

            return $dealer->refresh();
        });
    }

    /** Every new dealer starts on the free plan so routing has a cap to read. */
    public function startFreePlan(Dealer $dealer): void
    {
        $plan = Plan::where('slug', 'free')->where('audience', 'dealer')->first();

        if (! $plan || $dealer->subscriptions()->where('status', 'active')->exists()) {
            return;
        }

        $dealer->subscriptions()->create([
            'plan_id' => $plan->id,
            'starts_at' => today(),
            'ends_at' => today()->addYear(),
            'status' => 'active',
        ]);
    }

    /**
     * How much of this month's lead allowance the dealer has used — the number
     * their dashboard leads with.
     *
     * @return array{used: int, limit: int, daily_used: int, daily_cap: int, plan: ?string}
     */
    public function planUsage(Dealer $dealer): array
    {
        $subscription = $dealer->activeSubscription()->with('plan')->first();
        $plan = $subscription?->plan;

        return [
            'used' => $dealer->leadAssignments()
                ->whereBetween('assigned_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'limit' => (int) ($plan->lead_limit ?? 0),
            'daily_used' => $dealer->leadAssignments()->whereDate('assigned_at', today())->count(),
            'daily_cap' => (int) ($plan->daily_lead_cap ?? 0),
            'plan' => $plan?->name,
        ];
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Dealer::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
