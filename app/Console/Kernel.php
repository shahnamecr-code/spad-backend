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
        // Separate opt-in publisher. Its own settings default to disabled.
        $schedule->command('spad:whatsapp-market')
            ->name('spad-whatsapp-market')->everyMinute()->withoutOverlapping();
        // Render approved posters with real prices in CLI, not the PHP-FPM request.
        // This is a read-only image refresh; no WhatsApp messages are sent.
        $schedule->command('spad:wa-preview-warm')
            ->name('spad-whatsapp-admin-preview')->everyFiveMinutes()->withoutOverlapping();
        // On-demand cache misses are built only by CLI, on existing server cron.
        $schedule->command('spad:wa-preview-process')
            ->name('spad-whatsapp-preview-requests')->everyMinute()->withoutOverlapping();
        // Independent opt-in self-chat only, disabled before user QR pairing.
        $schedule->command('spad:wa-personal-dispatch')
            ->name('spad-whatsapp-personal-self')->everyMinute()->withoutOverlapping();
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
