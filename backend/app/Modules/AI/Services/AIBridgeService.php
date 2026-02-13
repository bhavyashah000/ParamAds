<?php

namespace App\Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIBridgeService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('paramads.ai_service_url', 'http://ai-service:8001');
    }

    /**
     * Get metric forecast.
     */
    public function forecast(int $campaignId, string $metric, array $historicalData, int $forecastDays = 7): array
    {
        return $this->post('/api/v1/forecast', [
            'campaign_id' => $campaignId,
            'metric' => $metric,
            'historical_data' => $historicalData,
            'forecast_days' => $forecastDays,
        ]);
    }

    /**
     * Get budget forecast.
     */
    public function forecastBudget(array $spendData, array $revenueData, float $targetRoas, array $budgetRange): array
    {
        return $this->post('/api/v1/forecast/budget', [
            'spend_data' => $spendData,
            'revenue_data' => $revenueData,
            'target_roas' => $targetRoas,
            'budget_range' => $budgetRange,
        ]);
    }

    /**
     * Detect anomalies.
     */
    public function detectAnomalies(int $campaignId, string $metric, array $dataPoints, float $sensitivity = 2.0): array
    {
        return $this->post('/api/v1/anomaly/detect', [
            'campaign_id' => $campaignId,
            'metric' => $metric,
            'data_points' => $dataPoints,
            'sensitivity' => $sensitivity,
        ]);
    }

    /**
     * Detect multi-metric anomalies.
     */
    public function detectMultiMetricAnomalies(int $campaignId, array $metricsData, float $sensitivity = 2.0): array
    {
        return $this->post('/api/v1/anomaly/detect-multi', [
            'campaign_id' => $campaignId,
            'metrics_data' => $metricsData,
            'sensitivity' => $sensitivity,
        ]);
    }

    /**
     * Get budget optimization recommendations.
     */
    public function optimizeBudget(array $campaignData): array
    {
        return $this->post('/api/v1/budget/optimize', $campaignData);
    }

    /**
     * Generate NL insights.
     */
    public function generateInsights(int $orgId, array $campaignData, string $timeRange = '7d'): array
    {
        return $this->post('/api/v1/insights/generate', [
            'organization_id' => $orgId,
            'campaign_data' => $campaignData,
            'time_range' => $timeRange,
        ]);
    }

    /**
     * Ask a natural language question.
     */
    public function askQuestion(string $question, array $contextData): array
    {
        return $this->post('/api/v1/insights/ask', [
            'question' => $question,
            'context_data' => $contextData,
        ]);
    }

    /**
     * Health check.
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Make a POST request to the AI service.
     */
    private function post(string $endpoint, array $data): array
    {
        try {
            $response = Http::timeout(60)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post("{$this->baseUrl}{$endpoint}", $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error("AI service error: {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['error' => 'AI service returned error: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error("AI service connection failed: {$endpoint}", [
                'error' => $e->getMessage(),
            ]);

            return ['error' => 'AI service unavailable: ' . $e->getMessage()];
        }
    }
}
