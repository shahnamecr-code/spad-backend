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
        // Short-retention, privacy-first CRM behavioral telemetry (90 days).
        $schedule->call(function () {
            if (!\Illuminate\Support\Facades\Schema::hasTable('spad_crm_behavior_events')) return;
            for ($i = 0; $i < 20; $i++) {
                $ids = \Illuminate\Support\Facades\DB::table('spad_crm_behavior_events')
                    ->where('created_at', '<', now()->subDays(90))
                    ->orderBy('id')->limit(500)->pluck('id')->all();
                if (!$ids) break;
                \Illuminate\Support\Facades\DB::table('spad_crm_behavior_events')->whereIn('id', $ids)->delete();
            }
        })->name('spad-crm-behavior-retention')->dailyAt('03:15')->withoutOverlapping();
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
