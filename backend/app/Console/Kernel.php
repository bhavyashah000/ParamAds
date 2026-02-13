<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ---- Phase 1: Core Sync ----

        // Metrics sync every 15 minutes
        $schedule->job(new \App\Modules\Metrics\Jobs\SyncMetricsJob())
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // Campaign sync every 30 minutes
        $schedule->job(new \App\Modules\Campaigns\Jobs\SyncCampaignsJob())
            ->everyThirtyMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // Process automation rules every 15 minutes
        $schedule->job(new \App\Modules\Automation\Jobs\ProcessAutomationRulesJob())
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->onOneServer();

        // ---- Phase 2: Creative Intelligence ----

        // Creative trend analysis hourly
        $schedule->job(new \App\Modules\CreativeIntelligence\Jobs\CreativeTrendJob())
            ->hourly()
            ->withoutOverlapping()
            ->onOneServer();

        // ---- Data Engineering ----

        // Daily ETL aggregation at 2 AM
        $schedule->job(new \App\Modules\DataEngineering\Jobs\DailyAggregationJob())
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->onOneServer();

        // Weekly data cleanup on Sundays at 3 AM
        $schedule->job(new \App\Modules\DataEngineering\Jobs\DataCleanupJob())
            ->weeklyOn(0, '03:00')
            ->withoutOverlapping()
            ->onOneServer();

        // ---- Maintenance ----

        // Token refresh check hourly
        // $schedule->job(new \App\Modules\AdAccounts\Jobs\RefreshTokensJob())
        //     ->hourly()
        //     ->withoutOverlapping()
        //     ->onOneServer();

        // Prune old Sanctum tokens daily
        $schedule->command('sanctum:prune-expired --hours=24')
            ->daily()
            ->onOneServer();

        // Clear expired cache daily
        $schedule->command('cache:prune-stale-tags')
            ->hourly()
            ->onOneServer();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
