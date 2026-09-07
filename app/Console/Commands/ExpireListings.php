<?php

namespace App\Console\Commands;

use App\Domain\Marketplace\Services\ListingService;
use App\Models\UsedListing;
use Illuminate\Console\Command;

class ExpireListings extends Command
{
    protected $signature = 'listings:expire';

    protected $description = 'Expire live listings past their expiry date';

    public function handle(ListingService $listings): int
    {
        $expired = 0;

        UsedListing::where('status', 'live')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->chunkById(100, function ($batch) use ($listings, &$expired) {
                foreach ($batch as $listing) {
                    $listings->expire($listing);
                    $expired++;
                }
            });

        $this->info("Expired {$expired} listing(s).");

        return self::SUCCESS;
    }
}
