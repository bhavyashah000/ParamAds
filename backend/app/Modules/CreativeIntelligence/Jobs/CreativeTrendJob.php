<?php

namespace App\Modules\CreativeIntelligence\Jobs;

use App\Modules\CreativeIntelligence\Services\CreativeAnalysisService;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreativeTrendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function handle(CreativeAnalysisService $service): void
    {
        Organization::chunk(50, function ($organizations) use ($service) {
            foreach ($organizations as $org) {
                try {
                    $service->analyzeOrganization($org->id);
                } catch (\Exception $e) {
                    Log::error("Creative analysis failed for org {$org->id}: " . $e->getMessage());
                }
            }
        });
    }
}
