<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Rate Limiting Middleware
 *
 * Applies different rate limits based on user tier (free, pro, enterprise).
 * Prevents API abuse while allowing higher limits for paid plans.
 *
 * @see config/rate-limiting.php for rate limit configuration
 */
class ThrottleApiRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $tier = 'free'): Response
    {
        // Get rate limit configuration for the specified tier
        $config = config("rate-limiting.api.{$tier}");

        if ($config === null) {
            // Default to free tier if tier not found
            $config = config('rate-limiting.api.free');
        }

        $limit = $config['limit'];
        $decayMinutes = $config['decay_minutes'];

        // Create unique key for this user/IP
        $key = $this->resolveRequestSignature($request, $tier);

        // Attempt to increment the rate limiter
        $rateLimiter = RateLimiter::attempt(
            $key,
            $limit,
            function () {
                // This callback is executed if within limits
            },
            $decayMinutes * 60 // Convert to seconds
        );

        if (! $rateLimiter) {
            // Rate limit exceeded
            return $this->buildRateLimitExceededResponse($request, $key, $limit, $decayMinutes);
        }

        // Add rate limit headers to response
        $response = $next($request);

        return $this->addRateLimitHeaders(
            $response,
            $limit,
            RateLimiter::remaining($key, $limit),
            RateLimiter::availableIn($key)
        );
    }

    /**
     * Resolve the rate limiter key for the request.
     */
    protected function resolveRequestSignature(Request $request, string $tier): string
    {
        $user = $request->user();

        if ($user !== null) {
            // Authenticated users: rate limit by user ID
            return 'api:throttle:'.$tier.':'.$user->id;
        }

        // Unauthenticated requests: rate limit by IP address
        return 'api:throttle:'.$tier.':'.$request->ip();
    }

    /**
     * Create a rate limit exceeded response.
     */
    protected function buildRateLimitExceededResponse(
        Request $request,
        string $key,
        int $limit,
        int $decayMinutes
    ): Response {
        $retryAfter = RateLimiter::availableIn($key);

        return response()->json([
            'error' => 'Too Many Requests',
            'message' => "Rate limit exceeded. Maximum {$limit} requests per {$decayMinutes} minute(s).",
            'retry_after' => $retryAfter,
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            'Retry-After' => $retryAfter,
        ]);
    }

    /**
     * Add rate limit headers to the response.
     */
    protected function addRateLimitHeaders(
        Response $response,
        int $limit,
        int $remaining,
        int $resetIn
    ): Response {
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', (string) now()->addSeconds($resetIn)->timestamp);

        return $response;
    }

    /**
     * Get the user's tier based on their subscription.
     *
     * This method will be used when subscription system is implemented.
     * For now, returns 'free' for all users.
     *
     * @param  \App\Models\User|null  $user
     * @return string 'free', 'pro', or 'enterprise'
     */
    protected function getUserTier($user): string
    {
        if ($user === null) {
            return 'free';
        }

        // TODO: Implement subscription/tier logic when billing is added
        // Example:
        // return $user->subscription?->plan ?? 'free';

        return 'free';
    }
}
