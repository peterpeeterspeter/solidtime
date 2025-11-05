<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Services\ApiKeyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function __construct(
        protected ApiKeyService $apiKeyService
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$requiredScopes): Response
    {
        $apiKeyHeader = $request->header('Authorization');

        if (! $apiKeyHeader) {
            return response()->json([
                'error' => 'API key required',
                'message' => 'Please provide an API key in the Authorization header',
            ], 401);
        }

        // Extract key from "Bearer sk_..." format
        $apiKey = $this->extractKeyFromHeader($apiKeyHeader);

        if (! $apiKey) {
            return response()->json([
                'error' => 'Invalid API key format',
                'message' => 'Authorization header must be in format: Bearer sk_...',
            ], 401);
        }

        // Authenticate
        $authenticatedKey = $this->apiKeyService->authenticate($apiKey);

        if (! $authenticatedKey) {
            return response()->json([
                'error' => 'Invalid API key',
                'message' => 'The provided API key is invalid or expired',
            ], 401);
        }

        // Check required scopes
        if (! empty($requiredScopes)) {
            $hasRequiredScope = false;

            foreach ($requiredScopes as $scope) {
                if ($authenticatedKey->hasScope($scope)) {
                    $hasRequiredScope = true;
                    break;
                }
            }

            if (! $hasRequiredScope) {
                return response()->json([
                    'error' => 'Insufficient permissions',
                    'message' => 'This API key does not have the required scopes: ' . implode(', ', $requiredScopes),
                    'required_scopes' => $requiredScopes,
                    'your_scopes' => $authenticatedKey->scopes,
                ], 403);
            }
        }

        // Attach authenticated API key and user to request
        $request->merge([
            'api_key' => $authenticatedKey,
            'authenticated_user' => $authenticatedKey->user,
            'authenticated_organization' => $authenticatedKey->organization,
        ]);

        // Set auth user for convenience
        auth()->setUser($authenticatedKey->user);

        return $next($request);
    }

    /**
     * Extract API key from Authorization header
     */
    protected function extractKeyFromHeader(string $header): ?string
    {
        // Support both "Bearer sk_..." and "sk_..." formats
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        if (str_starts_with($header, 'sk_')) {
            return $header;
        }

        return null;
    }
}
