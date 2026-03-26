<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\UsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateApiKey
{
    /**
     * Handle an incoming API request.
     *
     * This single middleware handles:
     *   1. API key authentication
     *   2. Plan limit enforcement (rate limiting by usage count)
     *   3. Usage logging after the response
     *   4. last_used_at tracking on the key
     *
     * Usage in routes:
     *   Route::post('/scrape', ...)->middleware('api.key:scrape');
     *   Route::post('/pdf', ...)->middleware('api.key:pdf');
     *   Route::post('/email', ...)->middleware('api.key:email');
     */
    public function handle(Request $request, Closure $next, string $endpoint): Response
    {
        // ── Step 1: Extract raw key ────────────────────────────────
        $raw = $request->bearerToken();

        if (! $raw) {
            return response()->json([
                'error' => 'Missing API key.',
                'hint'  => 'Add header: Authorization: Bearer YOUR_KEY',
                'docs'  => 'https://docs.autolib.dev/authentication',
            ], 401);
        }

        // ── Step 2: Validate key against DB ───────────────────────
        $apiKey = ApiKey::findByRaw($raw);

        if (! $apiKey) {
            return response()->json([
                'error' => 'Invalid or revoked API key.',
                'docs'  => 'https://docs.autolib.dev/authentication',
            ], 401);
        }

        $user = $apiKey->user;

        // ── Step 3: Check plan usage limit ────────────────────────
        if ($user->hasReachedLimit($endpoint)) {
            $limit = $user->planLimits()[$endpoint];
            $used  = $user->usageThisMonth($endpoint);

            return response()->json([
                'error'      => "Monthly {$endpoint} limit reached.",
                'used'       => $used,
                'limit'      => $limit,
                'plan'       => $user->plan,
                'resets'     => now()->endOfMonth()->toDateString(),
                'upgrade'    => 'https://autolib.dev/billing',
            ], 429);
        }

        $request->merge([
            'auth_user'    => $user,
            'auth_api_key' => $apiKey,
        ]);

        $startTime = microtime(true);
        $response  = $next($request);
        $ms        = (int) ((microtime(true) - $startTime) * 1000);

        UsageLog::create([
            'user_id'     => $user->id,
            'api_key_id'  => $apiKey->id,
            'endpoint'    => $endpoint,
            'ip_address'  => $request->ip(),
            'response_ms' => $ms,
            'status_code' => $response->getStatusCode(),
            'created_at'  => now(),
        ]);

        // ── Step 7: Update last_used_at ───────────────────────────
        $apiKey->updateQuietly(['last_used_at' => now()]);

        // ── Step 8: Add usage headers to response ─────────────────
        $used  = $user->usageThisMonth($endpoint);
        $limit = $user->planLimits()[$endpoint];

        $response->headers->set('X-RateLimit-Limit',     $limit === PHP_INT_MAX ? 'unlimited' : $limit);
        $response->headers->set('X-RateLimit-Remaining', $limit === PHP_INT_MAX ? 'unlimited' : max(0, $limit - $used));
        $response->headers->set('X-RateLimit-Reset',     now()->endOfMonth()->timestamp);

        return $response;
    }
}