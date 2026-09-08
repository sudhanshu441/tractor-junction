<?php

namespace App\Console\Commands;

use App\Models\Blog;
use Illuminate\Console\Command;

/**
 * Flips scheduled posts to published once their time arrives.
 *
 * The public scope already treats a due scheduled post as visible, so this is
 * about the admin list telling the truth rather than about the website.
 */
class PublishScheduledPosts extends Command
{
    protected $signature = 'content:publish-scheduled';

    protected $description = 'Publish scheduled posts whose time has come';

    public function handle(): int
    {
        $count = Blog::where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $this->info("Published {$count} post(s).");

        return self::SUCCESS;
    }
}
