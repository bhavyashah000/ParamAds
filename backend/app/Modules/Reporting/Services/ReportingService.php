<?php

namespace App\Modules\Reporting\Services;

use App\Modules\Reporting\Models\ScheduledReport;
use App\Modules\Reporting\Models\ReportHistory;
use App\Modules\Metrics\Models\CampaignMetric;
use App\Modules\Campaigns\Models\Campaign;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ReportingService
{
    /**
     * Create a scheduled report.
     */
    public function createReport(int $orgId, int $userId, array $data): ScheduledReport
    {
        return ScheduledReport::create([
            'organization_id' => $orgId,
            'created_by' => $userId,
            'name' => $data['name'],
            'type' => $data['type'],
            'frequency' => $data['frequency'],
            'format' => $data['format'] ?? 'pdf',
            'filters' => $data['filters'] ?? [],
            'recipients' => $data['recipients'],
            'sections' => $data['sections'] ?? $this->getDefaultSections($data['type']),
            'status' => 'active',
            'next_send_at' => $this->calculateNextSendAt($data['frequency']),
        ]);
    }

    /**
     * Generate a report.
     */
    public function generateReport(ScheduledReport $report): ReportHistory
    {
        try {
            $data = $this->gatherReportData($report);
            $content = $this->buildReportContent($report, $data);

            // Store report
            $filename = "reports/{$report->organization_id}/{$report->id}/" . now()->format('Y-m-d_His') . ".{$report->format}";
            Storage::put($filename, $content);

            $history = ReportHistory::create([
                'scheduled_report_id' => $report->id,
                'organization_id' => $report->organization_id,
                'file_url' => $filename,
                'status' => 'generated',
                'metadata' => [
                    'generated_at' => now()->toIso8601String(),
                    'data_range' => $this->getDateRange($report),
                ],
            ]);

            // Send to recipients
            $this->sendReport($report, $history);

            // Update schedule
            $report->update([
                'last_sent_at' => now(),
                'next_send_at' => $this->calculateNextSendAt($report->frequency),
            ]);

            return $history;
        } catch (\Exception $e) {
            Log::error("Report generation failed: {$report->id}", ['error' => $e->getMessage()]);

            return ReportHistory::create([
                'scheduled_report_id' => $report->id,
                'organization_id' => $report->organization_id,
                'status' => 'failed',
                'metadata' => ['error' => $e->getMessage()],
            ]);
        }
    }

    /**
     * Generate on-demand report.
     */
    public function generateOnDemand(int $orgId, array $options): array
    {
        $dateFrom = $options['date_from'] ?? now()->subDays(7)->format('Y-m-d');
        $dateTo = $options['date_to'] ?? now()->format('Y-m-d');

        $campaigns = Campaign::where('organization_id', $orgId)
            ->when($options['campaign_ids'] ?? null, fn($q, $ids) => $q->whereIn('id', $ids))
            ->get();

        $metrics = CampaignMetric::whereIn('campaign_id', $campaigns->pluck('id'))
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();

        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'summary' => [
                'total_spend' => round($metrics->sum('spend'), 2),
                'total_impressions' => $metrics->sum('impressions'),
                'total_clicks' => $metrics->sum('clicks'),
                'total_conversions' => $metrics->sum('conversions'),
                'total_revenue' => round($metrics->sum('revenue'), 2),
                'roas' => $metrics->sum('spend') > 0
                    ? round($metrics->sum('revenue') / $metrics->sum('spend'), 4) : 0,
                'ctr' => $metrics->sum('impressions') > 0
                    ? round(($metrics->sum('clicks') / $metrics->sum('impressions')) * 100, 4) : 0,
                'cpa' => $metrics->sum('conversions') > 0
                    ? round($metrics->sum('spend') / $metrics->sum('conversions'), 2) : 0,
            ],
            'campaigns' => $campaigns->map(function ($campaign) use ($metrics) {
                $campaignMetrics = $metrics->where('campaign_id', $campaign->id);
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'platform' => $campaign->platform,
                    'status' => $campaign->status,
                    'spend' => round($campaignMetrics->sum('spend'), 2),
                    'impressions' => $campaignMetrics->sum('impressions'),
                    'clicks' => $campaignMetrics->sum('clicks'),
                    'conversions' => $campaignMetrics->sum('conversions'),
                    'revenue' => round($campaignMetrics->sum('revenue'), 2),
                    'roas' => $campaignMetrics->sum('spend') > 0
                        ? round($campaignMetrics->sum('revenue') / $campaignMetrics->sum('spend'), 4) : 0,
                ];
            })->toArray(),
            'daily_breakdown' => $metrics->groupBy('date')
                ->map(function ($dayMetrics, $date) {
                    return [
                        'date' => $date,
                        'spend' => round($dayMetrics->sum('spend'), 2),
                        'conversions' => $dayMetrics->sum('conversions'),
                        'revenue' => round($dayMetrics->sum('revenue'), 2),
                    ];
                })->values()->toArray(),
        ];
    }

    // ---- Private helpers ----

    private function gatherReportData(ScheduledReport $report): array
    {
        $dateRange = $this->getDateRange($report);
        return $this->generateOnDemand($report->organization_id, [
            'date_from' => $dateRange['from'],
            'date_to' => $dateRange['to'],
            'campaign_ids' => $report->filters['campaign_ids'] ?? null,
        ]);
    }

    private function buildReportContent(ScheduledReport $report, array $data): string
    {
        if ($report->format === 'csv') {
            return $this->buildCsvReport($data);
        }
        // Default to JSON for now; PDF generation would use a package like DomPDF
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function buildCsvReport(array $data): string
    {
        $lines = ["Campaign,Platform,Spend,Impressions,Clicks,Conversions,Revenue,ROAS"];
        foreach ($data['campaigns'] ?? [] as $campaign) {
            $lines[] = implode(',', [
                '"' . str_replace('"', '""', $campaign['name']) . '"',
                $campaign['platform'],
                $campaign['spend'],
                $campaign['impressions'],
                $campaign['clicks'],
                $campaign['conversions'],
                $campaign['revenue'],
                $campaign['roas'],
            ]);
        }
        return implode("\n", $lines);
    }

    private function sendReport(ScheduledReport $report, ReportHistory $history): void
    {
        // In production, use Laravel Mail with proper templates
        Log::info("Report {$report->id} generated and ready for delivery to: " . implode(', ', $report->recipients));
        $history->update(['status' => 'sent']);
    }

    private function getDateRange(ScheduledReport $report): array
    {
        return match ($report->frequency) {
            'daily' => ['from' => now()->subDay()->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
            'weekly' => ['from' => now()->subWeek()->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
            'monthly' => ['from' => now()->subMonth()->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
            default => ['from' => now()->subWeek()->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
        };
    }

    private function calculateNextSendAt(string $frequency): string
    {
        return match ($frequency) {
            'daily' => now()->addDay()->startOfDay()->addHours(8)->toDateTimeString(),
            'weekly' => now()->addWeek()->startOfWeek()->addHours(8)->toDateTimeString(),
            'monthly' => now()->addMonth()->startOfMonth()->addHours(8)->toDateTimeString(),
            default => now()->addWeek()->toDateTimeString(),
        };
    }

    private function getDefaultSections(string $type): array
    {
        return match ($type) {
            'performance' => ['summary', 'campaign_breakdown', 'daily_trends', 'top_performers'],
            'creative' => ['summary', 'top_creatives', 'fatigued_creatives', 'creative_trends'],
            'audience' => ['summary', 'audience_overview', 'overlap_analysis'],
            'budget' => ['summary', 'spend_breakdown', 'recommendations'],
            'executive' => ['summary', 'kpi_overview', 'highlights', 'recommendations'],
            default => ['summary'],
        };
    }
}
