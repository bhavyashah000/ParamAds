<?php

namespace App\Modules\Billing\Controllers;

use App\Modules\Organizations\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeWebhookController extends WebhookController
{
    /**
     * Handle invoice payment succeeded.
     */
    public function handleInvoicePaymentSucceeded(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if ($stripeId) {
            $organization = Organization::where('stripe_id', $stripeId)->first();
            if ($organization) {
                \DB::table('billing_events')->insert([
                    'organization_id' => $organization->id,
                    'event_type' => 'payment.succeeded',
                    'stripe_event_id' => $payload['id'] ?? null,
                    'payload' => json_encode($payload['data']['object']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Handle invoice payment failed.
     */
    public function handleInvoicePaymentFailed(array $payload): void
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;

        if ($stripeId) {
            $organization = Organization::where('stripe_id', $stripeId)->first();
            if ($organization) {
                \DB::table('billing_events')->insert([
                    'organization_id' => $organization->id,
                    'event_type' => 'payment.failed',
                    'stripe_event_id' => $payload['id'] ?? null,
                    'payload' => json_encode($payload['data']['object']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::warning("Payment failed for organization {$organization->id}");
                // TODO: Send payment failed notification
            }
        }
    }

    /**
     * Handle subscription deleted.
     */
    public function handleCustomerSubscriptionDeleted(array $payload): void
    {
        parent::handleCustomerSubscriptionDeleted($payload);

        $stripeId = $payload['data']['object']['customer'] ?? null;

        if ($stripeId) {
            $organization = Organization::where('stripe_id', $stripeId)->first();
            if ($organization) {
                $organization->update(['plan' => 'free']);

                \DB::table('billing_events')->insert([
                    'organization_id' => $organization->id,
                    'event_type' => 'subscription.deleted',
                    'stripe_event_id' => $payload['id'] ?? null,
                    'payload' => json_encode($payload['data']['object']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
