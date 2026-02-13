<?php

namespace App\Modules\CreativeIntelligence\Services;

use App\Modules\CreativeIntelligence\Models\Creative;
use App\Modules\CreativeIntelligence\Models\CreativeScore;
use App\Modules\CreativeIntelligence\Models\CreativeFatigue;
use App\Modules\Metrics\Models\AdMetric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreativeAnalysisService
{
    /**
     * Analyze and score all creatives for an organization.
     */
    public function analyzeOrganization(int $orgId): void
    {
        Creative::where('organization_id', $orgId)
            ->chunk(100, function ($creatives) {
                foreach ($creatives as $creative) {
                    $this->scoreCreative($creative);
                    $this->detectFatigue($creative);
                }
            });
    }

    /**
     * Score a single creative based on performance metrics.
     */
    public function scoreCreative(Creative $creative): CreativeScore
    {
        $metrics = AdMetric::where('ad_id', $creative->ad_id)
            ->where('date', '>=', now()->subDays(7))
            ->get();

        if ($metrics->isEmpty()) {
            return $this->createDefaultScore($creative);
        }

        $totalImpressions = $metrics->sum('impressions');
        $totalClicks = $metrics->sum('clicks');
        $totalConversions = $metrics->sum('conversions');
        $totalSpend = $metrics->sum('spend');

        $ctr = $totalImpressions > 0 ? ($totalClicks / $totalImpressions) * 100 : 0;
        $cvr = $totalClicks > 0 ? ($totalConversions / $totalClicks) * 100 : 0;
        $cpa = $totalConversions > 0 ? $totalSpend / $totalConversions : 0;

        // Calculate component scores (0-100)
        $engagementScore = $this->calculateEngagementScore($ctr, $totalClicks);
        $conversionScore = $this->calculateConversionScore($cvr, $totalConversions, $cpa);
        $relevanceScore = $this->calculateRelevanceScore($creative, $ctr);
        $fatigueScore = $this->calculateFatigueScore($creative);

        // Weighted overall score
        $overallScore = round(
            ($engagementScore * 0.25) +
            ($conversionScore * 0.35) +
            ($relevanceScore * 0.20) +
            ($fatigueScore * 0.20),
            1
        );

        return CreativeScore::create([
            'organization_id' => $creative->organization_id,
            'creative_id' => $creative->id,
            'date' => now()->format('Y-m-d'),
            'overall_score' => $overallScore,
            'engagement_score' => $engagementScore,
            'conversion_score' => $conversionScore,
            'relevance_score' => $relevanceScore,
            'fatigue_score' => $fatigueScore,
            'impressions' => $totalImpressions,
            'clicks' => $totalClicks,
            'conversions' => $totalConversions,
            'spend' => $totalSpend,
            'ctr' => round($ctr, 4),
            'cvr' => round($cvr, 4),
            'cpa' => round($cpa, 2),
            'scoring_data' => [
                'period_days' => 7,
                'metrics_count' => $metrics->count(),
            ],
        ]);
    }

    /**
     * Detect creative fatigue.
     */
    public function detectFatigue(Creative $creative): ?CreativeFatigue
    {
        // Get last 14 days of daily metrics
        $dailyMetrics = AdMetric::where('ad_id', $creative->ad_id)
            ->where('date', '>=', now()->subDays(14))
            ->orderBy('date')
            ->get();

        if ($dailyMetrics->count() < 3) {
            return null;
        }

        $ctrTrend = $dailyMetrics->pluck('ctr')->toArray();
        $cvrTrend = $dailyMetrics->pluck('cvr', 'date')->toArray();
        $cpaTrend = $dailyMetrics->pluck('cpa', 'date')->toArray();

        // Calculate trend direction
        $ctrSlope = $this->calculateSlope(array_values($ctrTrend));
        $avgFrequency = $dailyMetrics->avg('frequency') ?? 0;

        // Determine fatigue level
        $fatigueLevel = $this->determineFatigueLevel($ctrSlope, $avgFrequency, $dailyMetrics);

        // Calculate days running
        $firstMetric = AdMetric::where('ad_id', $creative->ad_id)->orderBy('date')->first();
        $daysRunning = $firstMetric ? now()->diffInDays($firstMetric->date) : 0;

        // Estimate remaining days
        $estimatedDaysRemaining = $this->estimateRemainingDays($fatigueLevel, $ctrSlope, $daysRunning);

        // Generate recommendation
        $recommendation = $this->generateFatigueRecommendation($fatigueLevel, $avgFrequency, $daysRunning);

        return CreativeFatigue::updateOrCreate(
            [
                'creative_id' => $creative->id,
                'date' => now()->format('Y-m-d'),
            ],
            [
                'organization_id' => $creative->organization_id,
                'fatigue_level' => $fatigueLevel,
                'frequency' => round($avgFrequency, 2),
                'ctr_trend' => $ctrTrend,
                'cvr_trend' => $cvrTrend,
                'cpa_trend' => $cpaTrend,
                'days_running' => $daysRunning,
                'estimated_days_remaining' => $estimatedDaysRemaining,
                'recommendation' => $recommendation,
            ]
        );
    }

    /**
     * Get top performing creatives.
     */
    public function getTopPerformers(int $orgId, int $limit = 10): array
    {
        return CreativeScore::where('organization_id', $orgId)
            ->where('date', now()->format('Y-m-d'))
            ->orderByDesc('overall_score')
            ->limit($limit)
            ->with('creative')
            ->get()
            ->toArray();
    }

    /**
     * Get fatigued creatives that need attention.
     */
    public function getFatiguedCreatives(int $orgId): array
    {
        return CreativeFatigue::where('organization_id', $orgId)
            ->where('date', now()->format('Y-m-d'))
            ->whereIn('fatigue_level', ['high', 'critical'])
            ->with('creative')
            ->orderByDesc('fatigue_level')
            ->get()
            ->toArray();
    }

    // ---- Private scoring helpers ----

    private function calculateEngagementScore(float $ctr, int $clicks): float
    {
        // Benchmark CTR: 1% is average, 3%+ is excellent
        $ctrScore = min(100, ($ctr / 3) * 100);
        $volumeBonus = min(20, $clicks / 50);
        return min(100, round($ctrScore + $volumeBonus, 1));
    }

    private function calculateConversionScore(float $cvr, int $conversions, float $cpa): float
    {
        // Benchmark CVR: 2% is average, 5%+ is excellent
        $cvrScore = min(100, ($cvr / 5) * 100);
        $volumeBonus = min(20, $conversions * 2);
        return min(100, round($cvrScore + $volumeBonus, 1));
    }

    private function calculateRelevanceScore(Creative $creative, float $ctr): float
    {
        $score = 50; // Base score

        // Higher CTR indicates relevance
        if ($ctr > 2) $score += 20;
        elseif ($ctr > 1) $score += 10;

        // Has headline and body
        if ($creative->headline) $score += 10;
        if ($creative->body) $score += 10;
        if ($creative->cta) $score += 10;

        return min(100, $score);
    }

    private function calculateFatigueScore(Creative $creative): float
    {
        $latestFatigue = CreativeFatigue::where('creative_id', $creative->id)
            ->orderByDesc('date')
            ->first();

        if (!$latestFatigue) return 100; // No fatigue = perfect score

        return match ($latestFatigue->fatigue_level) {
            'none' => 100,
            'low' => 75,
            'medium' => 50,
            'high' => 25,
            'critical' => 5,
            default => 50,
        };
    }

    private function determineFatigueLevel(float $ctrSlope, float $frequency, $metrics): string
    {
        // Critical: steep CTR decline + high frequency
        if ($ctrSlope < -0.3 && $frequency > 5) return 'critical';
        if ($ctrSlope < -0.2 && $frequency > 3) return 'high';
        if ($ctrSlope < -0.1 && $frequency > 2) return 'medium';
        if ($ctrSlope < -0.05) return 'low';
        return 'none';
    }

    private function estimateRemainingDays(string $fatigueLevel, float $slope, int $daysRunning): int
    {
        return match ($fatigueLevel) {
            'critical' => 0,
            'high' => max(1, 3),
            'medium' => max(3, 7),
            'low' => max(7, 14),
            'none' => 30,
            default => 14,
        };
    }

    private function generateFatigueRecommendation(string $level, float $frequency, int $daysRunning): string
    {
        return match ($level) {
            'critical' => "Creative is severely fatigued (frequency: {$frequency}x, running {$daysRunning} days). Immediately pause and replace with fresh creative.",
            'high' => "Creative showing significant fatigue (frequency: {$frequency}x). Plan replacement within 3 days. Consider new angles or formats.",
            'medium' => "Early fatigue signals detected (frequency: {$frequency}x). Monitor closely and prepare backup creatives.",
            'low' => "Slight performance decline noted. Continue monitoring, no immediate action needed.",
            'none' => "Creative performing well. No fatigue detected.",
            default => "Unable to determine fatigue status.",
        };
    }

    private function calculateSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) return 0;

        $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) return 0;

        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    private function createDefaultScore(Creative $creative): CreativeScore
    {
        return CreativeScore::create([
            'organization_id' => $creative->organization_id,
            'creative_id' => $creative->id,
            'date' => now()->format('Y-m-d'),
            'overall_score' => 0,
            'engagement_score' => 0,
            'conversion_score' => 0,
            'relevance_score' => 0,
            'fatigue_score' => 100,
            'impressions' => 0, 'clicks' => 0, 'conversions' => 0,
            'spend' => 0, 'ctr' => 0, 'cvr' => 0, 'cpa' => 0,
            'scoring_data' => ['note' => 'No metrics available'],
        ]);
    }
}
