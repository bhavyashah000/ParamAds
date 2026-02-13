<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Check if organization has an active subscription for the required feature.
     */
    public function handle(Request $request, Closure $next, string $feature = ''): Response
    {
        $organization = $request->user()->organization;

        if (!$organization) {
            return response()->json([
                'message' => 'No organization found.',
            ], 403);
        }

        // Check if on free plan and feature requires paid
        if ($feature && $organization->plan === 'free') {
            return response()->json([
                'message' => 'This feature requires a paid subscription.',
                'required_feature' => $feature,
            ], 402);
        }

        // Check specific feature access
        if ($feature) {
            $billingService = app(\App\Modules\Billing\Services\BillingService::class);
            if (!$billingService->hasFeatureAccess($organization, $feature)) {
                return response()->json([
                    'message' => "Your current plan does not include {$feature}. Please upgrade.",
                    'required_feature' => $feature,
                ], 402);
            }
        }

        return $next($request);
    }
}
