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
