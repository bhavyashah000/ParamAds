<?php

namespace App\Modules\Billing\Services;

use App\Modules\Billing\Models\Plan;
use App\Modules\Organizations\Models\Organization;

class BillingService
{
    /**
     * Get available plans.
     */
    public function getPlans()
    {
        return Plan::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Subscribe organization to a plan.
     */
    public function subscribe(Organization $organization, string $planSlug, string $paymentMethodId): array
    {
        $plan = Plan::where('slug', $planSlug)->firstOrFail();

        // Create or update Stripe customer
        if (!$organization->stripe_id) {
            $organization->createAsStripeCustomer([
                'name' => $organization->name,
                'email' => $organization->email,
            ]);
        }

        // Add payment method
        $organization->addPaymentMethod($paymentMethodId);
        $organization->updateDefaultPaymentMethod($paymentMethodId);

        // Create subscription
        $subscription = $organization->newSubscription('default', $plan->stripe_price_id)
            ->create($paymentMethodId);

        // Update organization plan
        $organization->update(['plan' => $plan->slug]);

        return [
            'subscription' => $subscription,
            'plan' => $plan,
            'organization' => $organization->fresh(),
        ];
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Organization $organization, string $newPlanSlug): array
    {
        $plan = Plan::where('slug', $newPlanSlug)->firstOrFail();

        $subscription = $organization->subscription('default');

        if (!$subscription) {
            throw new \Exception('No active subscription found.');
        }

        $subscription->swap($plan->stripe_price_id);
        $organization->update(['plan' => $plan->slug]);

        return [
            'subscription' => $subscription->fresh(),
            'plan' => $plan,
        ];
    }

    /**
     * Cancel subscription.
     */
    public function cancelSubscription(Organization $organization): void
    {
        $subscription = $organization->subscription('default');

        if ($subscription) {
            $subscription->cancel();
        }

        $organization->update(['plan' => 'free']);
    }

    /**
     * Resume cancelled subscription.
     */
    public function resumeSubscription(Organization $organization): void
    {
        $subscription = $organization->subscription('default');

        if ($subscription && $subscription->onGracePeriod()) {
            $subscription->resume();
        }
    }

    /**
     * Get billing history.
     */
    public function getBillingHistory(Organization $organization)
    {
        return $organization->invoices();
    }

    /**
     * Get current subscription details.
     */
    public function getSubscriptionDetails(Organization $organization): array
    {
        $subscription = $organization->subscription('default');
        $plan = Plan::where('slug', $organization->plan)->first();

        return [
            'plan' => $plan,
            'subscription' => $subscription,
            'on_trial' => $organization->onTrial(),
            'on_grace_period' => $subscription ? $subscription->onGracePeriod() : false,
            'payment_method' => $organization->defaultPaymentMethod(),
        ];
    }

    /**
     * Check if organization has access to a feature based on plan.
     */
    public function hasFeatureAccess(Organization $organization, string $feature): bool
    {
        $plan = Plan::where('slug', $organization->plan)->first();

        if (!$plan) {
            return false;
        }

        return match ($feature) {
            'ai_features' => $plan->has_ai_features,
            'creative_intelligence' => $plan->has_creative_intelligence,
            'audience_intelligence' => $plan->has_audience_intelligence,
            'agency_features' => $plan->has_agency_features,
            'api_access' => $plan->has_api_access,
            default => false,
        };
    }
}
