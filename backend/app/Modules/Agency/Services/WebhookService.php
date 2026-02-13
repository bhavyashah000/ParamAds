<?php

namespace App\Modules\Agency\Services;

use App\Modules\Agency\Models\Webhook;
use App\Modules\Agency\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WebhookService
{
    /**
     * Supported webhook events.
     */
    public const EVENTS = [
        'campaign.created',
        'campaign.updated',
        'campaign.paused',
        'campaign.activated',
        'metrics.synced',
        'anomaly.detected',
        'automation.triggered',
        'creative.fatigued',
        'budget.alert',
        'report.generated',
    ];

    /**
     * Create a webhook.
     */
    public function create(int $orgId, array $data): Webhook
    {
        return Webhook::create([
            'organization_id' => $orgId,
            'name' => $data['name'],
            'url' => $data['url'],
            'secret' => Str::random(64),
            'events' => $data['events'],
            'active' => true,
        ]);
    }

    /**
     * Dispatch webhook event to all matching webhooks.
     */
    public function dispatch(int $orgId, string $event, array $payload): void
    {
        $webhooks = Webhook::where('organization_id', $orgId)
            ->where('active', true)
            ->get()
            ->filter(fn($w) => in_array($event, $w->events));

        foreach ($webhooks as $webhook) {
            $this->send($webhook, $event, $payload);
        }
    }

    /**
     * Send webhook payload.
     */
    public function send(Webhook $webhook, string $event, array $payload): void
    {
        $body = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $signature = hash_hmac('sha256', json_encode($body), $webhook->secret);

        $startTime = microtime(true);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, $body);

            $duration = (int) ((microtime(true) - $startTime) * 1000);
            $success = $response->successful();

            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event' => $event,
                'payload' => $body,
                'response_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 1000),
                'success' => $success,
                'duration_ms' => $duration,
            ]);

            if ($success) {
                $webhook->update([
                    'last_triggered_at' => now(),
                    'failure_count' => 0,
                ]);
            } else {
                $this->handleFailure($webhook);
            }
        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event' => $event,
                'payload' => $body,
                'response_code' => 0,
                'response_body' => $e->getMessage(),
                'success' => false,
                'duration_ms' => $duration,
            ]);

            $this->handleFailure($webhook);
            Log::error("Webhook delivery failed: {$webhook->id}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Handle webhook delivery failure.
     */
    private function handleFailure(Webhook $webhook): void
    {
        $webhook->increment('failure_count');
        $webhook->update(['last_failed_at' => now()]);

        // Disable after 10 consecutive failures
        if ($webhook->failure_count >= 10) {
            $webhook->update(['active' => false]);
            Log::warning("Webhook {$webhook->id} disabled after 10 consecutive failures.");
        }
    }
}
