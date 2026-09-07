<?php

namespace App\Console\Commands;

use App\Domain\Notification\NotificationDispatcher;
use App\Models\Lead;
use Illuminate\Console\Command;

class SendFollowUpReminders extends Command
{
    protected $signature = 'leads:followup-reminders';

    protected $description = 'Remind assignees of leads due for follow-up today';

    public function handle(NotificationDispatcher $notifications): int
    {
        $sent = 0;

        Lead::with('currentAssignment.user', 'currentAssignment.dealer.owner')
            ->dueToday()
            ->chunkById(100, function ($batch) use ($notifications, &$sent) {
                foreach ($batch as $lead) {
                    $user = $lead->currentAssignment?->user ?? $lead->currentAssignment?->dealer?->owner;

                    if (! $user) {
                        continue;
                    }

                    $notifications->send('lead.followup', $user, ['reference' => $lead->reference_no]);
                    $sent++;
                }
            });

        $this->info("Sent {$sent} follow-up reminder(s).");

        return self::SUCCESS;
    }
}
