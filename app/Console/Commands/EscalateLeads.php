<?php

namespace App\Console\Commands;

use App\Domain\Lead\Services\LeadService;
use App\Domain\Lead\Services\RoutingEngine;
use App\Models\LeadAssignment;
use Illuminate\Console\Command;

/**
 * A lead nobody answers is worth nothing. Past the SLA it goes to the next
 * eligible dealer, and the original dealer's response score takes the hit.
 */
class EscalateLeads extends Command
{
    protected $signature = 'leads:escalate';

    protected $description = 'Reassign leads a dealer has not responded to inside the SLA';

    public function handle(RoutingEngine $routing, LeadService $leads): int
    {
        $escalated = 0;

        LeadAssignment::overdue()->with('lead.leadable', 'dealer')->chunkById(100,
            function ($batch) use ($routing, $leads, &$escalated) {
                foreach ($batch as $assignment) {
                    $assignment->forceFill(['status' => 'expired'])->save();

                    if ($assignment->dealer) {
                        $assignment->dealer->forceFill([
                            'response_score' => max(0, (float) $assignment->dealer->response_score - 0.5),
                        ])->save();
                    }

                    $lead = $assignment->lead;

                    if (! $lead || ! in_array($lead->status, LeadService::OPEN_STATUSES, true)) {
                        continue;
                    }

                    $leads->addActivity($lead, 'note', __('No response inside the SLA — reassigned.'));
                    $routing->route($lead);
                    $escalated++;
                }
            });

        $this->info("Escalated {$escalated} lead(s).");

        return self::SUCCESS;
    }
}
