<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Drain the mail queues.
 *
 * Shared cPanel hosting has no supervisor, so instead of an always-on worker
 * this borrows the one cron line a Laravel deploy already needs
 * (`php artisan schedule:run`) and runs a short worker off it. It exits as
 * soon as the queues are empty — mail held back by the throttle is *delayed*,
 * not available, so a throttled backlog ends the run rather than spinning, and
 * the next tick picks it up.
 *
 * Priority queue first: a broadcast can never sit in front of a student
 * waiting for their login. A dedicated always-on worker (see .env.example) is
 * better if the host allows one; this is the floor, not the ceiling.
 */
Schedule::command('queue:work --queue='.config('mail.queues.priority', 'emails').','.config('mail.queues.bulk', 'emails-bulk').',default --stop-when-empty --max-time=280')
    ->everyFiveMinutes()
    ->withoutOverlapping(10)
    ->runInBackground();
