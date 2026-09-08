<?php

namespace App\Console\Commands;

use App\Domain\Catalog\Services\FacetService;
use App\Domain\Content\Services\ContentService;
use App\Domain\Finance\Services\InsuranceService;
use App\Models\Setting;
use Illuminate\Console\Command;

/**
 * Warms the caches a cold deploy would otherwise make the first visitor pay for.
 *
 * Run after every deploy and after a bulk import. Everything here is also
 * populated lazily on demand — this only decides who waits for it.
 */
class WarmCaches extends Command
{
    protected $signature = 'kj:warm';

    protected $description = 'Warm the caches the public pages read on every request';

    public function handle(
        ContentService $content,
        FacetService $facets,
        InsuranceService $insurance,
    ): int {
        Setting::all2();
        $this->line('  settings');

        foreach (['header', 'footer_1', 'footer_2', 'mobile'] as $menu) {
            $content->menu($menu);
        }
        $content->banners('home_slider');
        $content->testimonials();
        $this->line('  menus, banners, testimonials');

        foreach (['general', 'buying', 'selling', 'loan', 'dealer'] as $category) {
            $content->faqs($category);
        }
        $this->line('  faqs');

        foreach (['tractor', 'implement', 'harvester'] as $type) {
            $facets->for(null, [], $type);
        }
        $this->line('  catalogue facets');

        $insurance->partnersFor(null);
        $this->line('  insurance partners');

        $this->info('Caches warm.');

        return self::SUCCESS;
    }
}
