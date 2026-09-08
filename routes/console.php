<?php

use Illuminate\Support\Facades\Schedule;

/*
| Scheduled work. Everything here is idempotent and safe to re-run.
*/

Schedule::command('listings:expire')->hourly()->withoutOverlapping();
Schedule::command('listings:expiry-reminders')->dailyAt('09:00');
Schedule::command('boosts:expire')->hourly()->withoutOverlapping();
Schedule::command('leads:escalate')->everyThirtyMinutes()->withoutOverlapping();
Schedule::command('leads:followup-reminders')->dailyAt('09:30');

// Content and search
Schedule::command('content:publish-scheduled')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('seo:sitemap')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('kj:warm')->hourly()->withoutOverlapping();

// Backups. Verified nightly; the restore rehearsal is a human task — see docs/16-RUNBOOKS.md.
Schedule::command('kj:backup --verify')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
