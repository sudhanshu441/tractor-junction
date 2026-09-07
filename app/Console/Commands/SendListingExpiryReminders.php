<?php

namespace App\Console\Commands;

use App\Domain\Notification\NotificationDispatcher;
use App\Models\UsedListing;
use Illuminate\Console\Command;

class SendListingExpiryReminders extends Command
{
    protected $signature = 'listings:expiry-reminders';

    protected $description = 'Warn sellers whose listings expire soon';

    public function handle(NotificationDispatcher $notifications): int
    {
        $days = (int) config('kj.listings.reminder_days_before');
        $sent = 0;

        UsedListing::with('seller')
            ->where('status', 'live')
            ->whereNotNull('expires_at')
            // A single day's window, so a seller is reminded once, not every night.
            ->whereBetween('expires_at', [now()->addDays($days)->startOfDay(), now()->addDays($days)->endOfDay()])
            ->chunkById(100, function ($batch) use ($notifications, $days, &$sent) {
                foreach ($batch as $listing) {
                    $notifications->send('listing.expiring', $listing->seller, [
                        'reference' => $listing->reference_no,
                        'days' => $days,
                    ]);
                    $sent++;
                }
            });

        $this->info("Sent {$sent} expiry reminder(s).");

        return self::SUCCESS;
    }
}
