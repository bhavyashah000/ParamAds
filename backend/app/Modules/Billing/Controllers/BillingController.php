<?php

namespace App\Modules\Billing\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Billing\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        private BillingService $billingService
    ) {}

    /**
     * Get available plans.
     */
    public function plans(): JsonResponse
    {
        $plans = $this->billingService->getPlans();
        return response()->json(['data' => $plans]);
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|string|exists:plans,slug',
            'payment_method_id' => 'required|string',
        ]);

        $result = $this->billingService->subscribe(
            $request->user()->organization,
            $request->plan,
            $request->payment_method_id
        );

        return response()->json([
            'message' => 'Subscription created.',
            'data' => $result,
        ]);
    }

    /**
     * Change plan.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate([
            'plan' => 'required|string|exists:plans,slug',
        ]);

        $result = $this->billingService->changePlan(
            $request->user()->organization,
            $request->plan
        );

        return response()->json([
            'message' => 'Plan changed.',
            'data' => $result,
        ]);
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $this->billingService->cancelSubscription($request->user()->organization);

        return response()->json(['message' => 'Subscription cancelled.']);
    }

    /**
     * Resume subscription.
     */
    public function resume(Request $request): JsonResponse
    {
        $this->billingService->resumeSubscription($request->user()->organization);

        return response()->json(['message' => 'Subscription resumed.']);
    }

    /**
     * Get subscription details.
     */
    public function subscription(Request $request): JsonResponse
    {
        $details = $this->billingService->getSubscriptionDetails(
            $request->user()->organization
        );

        return response()->json(['data' => $details]);
    }

    /**
     * Get billing history.
     */
    public function history(Request $request): JsonResponse
    {
        $invoices = $this->billingService->getBillingHistory(
            $request->user()->organization
        );

        return response()->json(['data' => $invoices]);
    }
}
