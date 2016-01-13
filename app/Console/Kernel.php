<?php

namespace AdFinder\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \AdFinder\Console\Commands\Inspire::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('inspire')
                 ->hourly();

        $schedule->call('AdFinder\Http\Controllers\DuplitronController@runMatchJob')
                 ->everyThirtyMinutes()
                 ->name("runMatchJob")
                 ->withoutOverlapping();

        $schedule->call('AdFinder\Http\Controllers\DuplitronController@runTargetJob')
                 ->dailyAt('1:00')
                 ->name("runTargetJob")
                 ->withoutOverlapping();

    }
}
