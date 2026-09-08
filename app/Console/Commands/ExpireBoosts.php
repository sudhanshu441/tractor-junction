<?php

namespace App\Console\Commands;

use App\Models\ListingBoost;
use Illuminate\Console\Command;

/**
 * A promotion someone paid for lasts exactly as long as they paid for.
 *
 * Without this the is_featured flag set at checkout would never come down and a
 * seven-day package would promote the listing forever.
 */
class ExpireBoosts extends Command
{
    protected $signature = 'boosts:expire';

    protected $description = 'End listing promotions whose paid window has passed';

    public function handle(): int
    {
        $ended = 0;

        ListingBoost::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->chunkById(100, function ($batch) use (&$ended) {
                foreach ($batch as $boost) {
                    $boost->forceFill(['status' => 'expired'])->save();

                    // Another package may still be running on the same listing.
                    $stillPromoted = ListingBoost::where('used_listing_id', $boost->used_listing_id)
                        ->running()->exists();

                    if (! $stillPromoted) {
                        $boost->listing?->forceFill(['is_featured' => false])->save();
                    }

                    $ended++;
                }
            });

        $this->info("Ended {$ended} promotion(s).");

        return self::SUCCESS;
    }
}
