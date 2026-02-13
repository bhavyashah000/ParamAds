<?php

namespace App\Modules\Metrics\Jobs;

use App\Modules\AdAccounts\Models\AdAccount;
use App\Modules\Metrics\Services\MetricsSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;
    public int $timeout = 300;

    public function __construct(
        private ?int $adAccountId = null
    ) {}

    public function handle(MetricsSyncService $syncService): void
    {
        if ($this->adAccountId) {
            $adAccount = AdAccount::active()->find($this->adAccountId);
            if ($adAccount) {
                $syncService->syncAdAccount($adAccount);
            }
            return;
        }

        // Sync all active ad accounts
        AdAccount::active()->chunk(50, function ($accounts) use ($syncService) {
            foreach ($accounts as $account) {
                try {
                    $syncService->syncAdAccount($account);
                } catch (\Exception $e) {
                    Log::error("Metrics sync failed for account {$account->id}: " . $e->getMessage());
                }
            }
        });
    }
}
