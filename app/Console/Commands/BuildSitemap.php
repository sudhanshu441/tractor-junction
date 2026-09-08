<?php

namespace App\Console\Commands;

use App\Domain\Seo\Services\SitemapBuilder;
use Illuminate\Console\Command;

class BuildSitemap extends Command
{
    protected $signature = 'seo:sitemap';

    protected $description = 'Rebuild the chunked XML sitemaps';

    public function handle(SitemapBuilder $builder): int
    {
        $files = $builder->build();

        $this->info('Wrote '.count($files).' sitemap file(s).');

        foreach ($files as $file) {
            $this->line('  '.$file);
        }

        return self::SUCCESS;
    }
}
