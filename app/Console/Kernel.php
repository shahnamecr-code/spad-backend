<?php

namespace App\Console;

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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Records one genuine public HTTPS + database probe every five minutes,
        // provided the hosting server invokes Laravel schedule:run.
        $schedule->call(function () {
            \App\Services\SpadAdminHealthService::sample();
        })->name('spad-admin-public-health')->everyFiveMinutes()->withoutOverlapping();
        $schedule->call(function() {
            \App\Services\SpadCustomerSmsAutomation::processPending(8);
        })->name('spad-customer-sms-outbox')->everyMinute()->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
