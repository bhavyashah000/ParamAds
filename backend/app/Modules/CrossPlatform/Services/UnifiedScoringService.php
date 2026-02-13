<?php

namespace App\Modules\CrossPlatform\Services;

use App\Modules\Campaigns\Models\Campaign;
use App\Modules\Metrics\Models\CampaignMetric;
use Illuminate\Support\Facades\DB;

class UnifiedScoringService
{
    /**
     * Score weights for campaign health.
     */
    private array $weights = [
        'roas' => 0.30,
        'ctr' => 0.15,
        'cpa_efficiency' => 0.20,
        'conversion_volume' => 0.15,
        'trend' => 0.10,
        'stability' => 0.10,
    ];

    /**
     * Score all campaigns for an organization.
     */
    public function scoreOrganization(int $orgId): array
    {
        $campaigns = Campaign::where('organization_id', $orgId)
            ->where('status', 'active')
            ->get();

        $scores = [];
        foreach ($campaigns as $campaign) {
            $scores[] = $this->scoreCampaign($campaign);
        }

        // Sort by overall score descending
        usort($scores, fn($a, $b) => $b['overall_score'] <=> $a['overall_score']);

        return [
            'campaigns' => $scores,
            'summary' => $this->generateScoringSummary($scores),
        ];
    }

    /**
     * Score a single campaign.
     */
    public function scoreCampaign(Campaign $campaign): array
    {
        $recentMetrics = CampaignMetric::where('campaign_id', $campaign->id)
            ->where('date', '>=', now()->subDays(7))
            ->orderBy('date')
            ->get();

        $olderMetrics = CampaignMetric::where('campaign_id', $campaign->id)
            ->whereBetween('date', [now()->subDays(14), now()->subDays(7)])
            ->get();

        if ($recentMetrics->isEmpty()) {
            return $this->noDataScore($campaign);
        }

        // Calculate component scores
        $roasScore = $this->scoreRoas($recentMetrics);
        $ctrScore = $this->scoreCtr($recentMetrics);
        $cpaScore = $this->scoreCpaEfficiency($recentMetrics);
        $volumeScore = $this->scoreConversionVolume($recentMetrics);
        $trendScore = $this->scoreTrend($recentMetrics, $olderMetrics);
        $stabilityScore = $this->scoreStability($recentMetrics);

        // Weighted overall score
        $overall = round(
            ($roasScore * $this->weights['roas']) +
            ($ctrScore * $this->weights['ctr']) +
            ($cpaScore * $this->weights['cpa_efficiency']) +
            ($volumeScore * $this->weights['conversion_volume']) +
            ($trendScore * $this->weights['trend']) +
            ($stabilityScore * $this->weights['stability']),
            1
        );

        // Determine health status
        $health = match (true) {
            $overall >= 80 => 'excellent',
            $overall >= 60 => 'good',
            $overall >= 40 => 'fair',
            $overall >= 20 => 'poor',
            default => 'critical',
        };

        return [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'platform' => $campaign->platform,
            'overall_score' => $overall,
            'health' => $health,
            'components' => [
                'roas' => $roasScore,
                'ctr' => $ctrScore,
                'cpa_efficiency' => $cpaScore,
                'conversion_volume' => $volumeScore,
                'trend' => $trendScore,
                'stability' => $stabilityScore,
            ],
            'metrics_summary' => [
                'spend' => round($recentMetrics->sum('spend'), 2),
                'revenue' => round($recentMetrics->sum('revenue'), 2),
                'conversions' => $recentMetrics->sum('conversions'),
                'roas' => $recentMetrics->sum('spend') > 0
                    ? round($recentMetrics->sum('revenue') / $recentMetrics->sum('spend'), 2) : 0,
            ],
        ];
    }

    /**
     * Generate budget reallocation recommendations.
     */
    public function getBudgetRecommendations(int $orgId): array
    {
        $scores = $this->scoreOrganization($orgId);
        $campaigns = $scores['campaigns'];

        if (empty($campaigns)) {
            return ['recommendations' => [], 'message' => 'No active campaigns to analyze.'];
        }

        $totalBudget = array_sum(array_column($campaigns, 'metrics_summary'));
        $recommendations = [];

        foreach ($campaigns as $campaign) {
            $score = $campaign['overall_score'];
            $currentSpend = $campaign['metrics_summary']['spend'] ?? 0;

            if ($score >= 80 && $campaign['metrics_summary']['roas'] > 2) {
                $recommendations[] = [
                    'campaign_id' => $campaign['campaign_id'],
                    'campaign_name' => $campaign['campaign_name'],
                    'action' => 'increase',
                    'suggested_change_percent' => 20,
                    'reason' => "High performance score ({$score}) with strong ROAS. Scale up to capture more conversions.",
                    'priority' => 'high',
                ];
            } elseif ($score < 30) {
                $recommendations[] = [
                    'campaign_id' => $campaign['campaign_id'],
                    'campaign_name' => $campaign['campaign_name'],
                    'action' => 'decrease',
                    'suggested_change_percent' => -50,
                    'reason' => "Low performance score ({$score}). Reduce budget and reallocate to better performers.",
                    'priority' => 'high',
                ];
            } elseif ($score < 50 && $campaign['metrics_summary']['roas'] < 1) {
                $recommendations[] = [
                    'campaign_id' => $campaign['campaign_id'],
                    'campaign_name' => $campaign['campaign_name'],
                    'action' => 'pause',
                    'suggested_change_percent' => -100,
                    'reason' => "Below breakeven ROAS with poor score ({$score}). Consider pausing.",
                    'priority' => 'critical',
                ];
            }
        }

        // Sort by priority
        $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($recommendations, fn($a, $b) => ($priorityOrder[$a['priority']] ?? 4) <=> ($priorityOrder[$b['priority']] ?? 4));

        return [
            'recommendations' => $recommendations,
            'total_campaigns' => count($campaigns),
            'avg_score' => round(array_sum(array_column($campaigns, 'overall_score')) / count($campaigns), 1),
        ];
    }

    // ---- Private scoring methods ----

    private function scoreRoas($metrics): float
    {
        $totalSpend = $metrics->sum('spend');
        $totalRevenue = $metrics->sum('revenue');
        $roas = $totalSpend > 0 ? $totalRevenue / $totalSpend : 0;

        // 0x = 0, 1x = 40, 2x = 60, 3x = 80, 5x+ = 100
        if ($roas >= 5) return 100;
        if ($roas >= 3) return 80 + (($roas - 3) / 2) * 20;
        if ($roas >= 2) return 60 + ($roas - 2) * 20;
        if ($roas >= 1) return 40 + ($roas - 1) * 20;
        return max(0, $roas * 40);
    }

    private function scoreCtr($metrics): float
    {
        $impressions = $metrics->sum('impressions');
        $clicks = $metrics->sum('clicks');
        $ctr = $impressions > 0 ? ($clicks / $impressions) * 100 : 0;

        // 0% = 0, 1% = 50, 2% = 70, 3%+ = 90, 5%+ = 100
        if ($ctr >= 5) return 100;
        if ($ctr >= 3) return 90 + (($ctr - 3) / 2) * 10;
        if ($ctr >= 2) return 70 + ($ctr - 2) * 20;
        if ($ctr >= 1) return 50 + ($ctr - 1) * 20;
        return max(0, $ctr * 50);
    }

    private function scoreCpaEfficiency($metrics): float
    {
        $totalSpend = $metrics->sum('spend');
        $totalConversions = $metrics->sum('conversions');
        if ($totalConversions == 0) return 0;

        $cpa = $totalSpend / $totalConversions;
        $revenue = $metrics->sum('revenue');
        $avgOrderValue = $totalConversions > 0 ? $revenue / $totalConversions : 0;

        // CPA as percentage of AOV (lower is better)
        if ($avgOrderValue <= 0) return 50;
        $ratio = $cpa / $avgOrderValue;

        if ($ratio <= 0.1) return 100;
        if ($ratio <= 0.2) return 80;
        if ($ratio <= 0.3) return 60;
        if ($ratio <= 0.5) return 40;
        return max(0, (1 - $ratio) * 40);
    }

    private function scoreConversionVolume($metrics): float
    {
        $conversions = $metrics->sum('conversions');
        $days = max(1, $metrics->pluck('date')->unique()->count());
        $dailyAvg = $conversions / $days;

        // Scale: 0/day = 0, 1/day = 30, 5/day = 60, 10/day = 80, 50+/day = 100
        if ($dailyAvg >= 50) return 100;
        if ($dailyAvg >= 10) return 80 + (($dailyAvg - 10) / 40) * 20;
        if ($dailyAvg >= 5) return 60 + (($dailyAvg - 5) / 5) * 20;
        if ($dailyAvg >= 1) return 30 + (($dailyAvg - 1) / 4) * 30;
        return max(0, $dailyAvg * 30);
    }

    private function scoreTrend($recent, $older): float
    {
        if ($older->isEmpty()) return 50; // Neutral

        $recentRoas = $recent->sum('spend') > 0
            ? $recent->sum('revenue') / $recent->sum('spend') : 0;
        $olderRoas = $older->sum('spend') > 0
            ? $older->sum('revenue') / $older->sum('spend') : 0;

        if ($olderRoas == 0) return 50;

        $change = ($recentRoas - $olderRoas) / $olderRoas;

        // -50% = 0, 0% = 50, +50% = 100
        return max(0, min(100, 50 + ($change * 100)));
    }

    private function scoreStability($metrics): float
    {
        if ($metrics->count() < 3) return 50;

        $roasValues = $metrics->map(function ($m) {
            return $m->spend > 0 ? $m->revenue / $m->spend : 0;
        })->toArray();

        $mean = array_sum($roasValues) / count($roasValues);
        if ($mean == 0) return 50;

        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $roasValues)) / count($roasValues);
        $cv = sqrt($variance) / $mean; // Coefficient of variation

        // Lower CV = more stable = higher score
        if ($cv <= 0.1) return 100;
        if ($cv <= 0.2) return 80;
        if ($cv <= 0.3) return 60;
        if ($cv <= 0.5) return 40;
        return max(0, (1 - $cv) * 40);
    }

    private function noDataScore(Campaign $campaign): array
    {
        return [
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'platform' => $campaign->platform,
            'overall_score' => 0,
            'health' => 'unknown',
            'components' => [],
            'metrics_summary' => [],
        ];
    }

    private function generateScoringSummary(array $scores): array
    {
        if (empty($scores)) return ['message' => 'No campaigns to score.'];

        $avgScore = array_sum(array_column($scores, 'overall_score')) / count($scores);
        $excellent = count(array_filter($scores, fn($s) => $s['health'] === 'excellent'));
        $critical = count(array_filter($scores, fn($s) => $s['health'] === 'critical'));

        return [
            'total_campaigns' => count($scores),
            'average_score' => round($avgScore, 1),
            'excellent_count' => $excellent,
            'critical_count' => $critical,
            'health_distribution' => array_count_values(array_column($scores, 'health')),
        ];
    }
}
