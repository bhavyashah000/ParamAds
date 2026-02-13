<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ApiRateLimit
{
    /**
     * Rate limits per plan.
     */
    private array $planLimits = [
        'free' => 60,        // 60 requests per minute
        'starter' => 120,
        'professional' => 300,
        'enterprise' => 600,
        'agency' => 1000,
        'agency_sub' => 300,
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $plan = $user?->organization?->plan ?? 'free';
        $limit = $this->planLimits[$plan] ?? 60;

        $key = 'api_rate:' . ($user?->organization_id ?? $request->ip());

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Rate limit exceeded.',
                'retry_after' => $retryAfter,
            ], 429)->withHeaders([
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
                'Retry-After' => $retryAfter,
            ]);
        }

        RateLimiter::hit($key, 60);

        $response = $next($request);

        $response->headers->set('X-RateLimit-Limit', $limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $limit - RateLimiter::attempts($key)));

        return $response;
    }
}
