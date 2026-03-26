<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\UsageLog;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    /**
     * GET /api/keys
     * List all API keys for the authenticated user (masked, no raw key).
     */
    public function index(Request $request)
    {
        $keys = $request->user()
            ->apiKeys()
            ->select('id', 'name', 'prefix', 'is_active', 'last_used_at', 'created_at')
            ->latest()
            ->get()
            ->map(function ($key) {
                $key->display = $key->prefix . '••••••••••••••••••••••••••••••'; // masked display
                return $key;
            });

        return response()->json(['data' => $keys]);
    }

    /**
     * POST /api/keys
     * Generate a new API key. Raw key is returned ONCE — store it somewhere safe.
     */
    public function store(Request $request)
    {
        $request->validate(['name' => 'nullable|string|max:64']);

        // Limit keys per plan
        $maxKeys = match ($request->user()->plan) {
            'starter'  => 1,
            'pro'      => 5,
            'business' => PHP_INT_MAX,
            default    => 1,
        };

        $currentCount = $request->user()->apiKeys()->where('is_active', true)->count();

        if ($currentCount >= $maxKeys) {
            return response()->json([
                'error'   => "Your {$request->user()->plan} plan allows {$maxKeys} active key(s).",
                'upgrade' => 'https://autolib.dev/billing',
            ], 403);
        }

        ['raw' => $raw, 'model' => $key] = ApiKey::generate(
            $request->user(),
            $request->input('name', 'My Key')
        );

        return response()->json([
            'message' => 'API key created. Copy this now — it will not be shown again.',
            'key'     => $raw,
            'id'      => $key->id,
            'prefix'  => $key->prefix,
        ], 201);
    }

    /**
     * DELETE /api/keys/{id}
     * Revoke (soft-delete) an API key.
     */
    public function destroy(Request $request, int $id)
    {
        $key = $request->user()->apiKeys()->findOrFail($id);
        $key->update(['is_active' => false]);

        return response()->json(['message' => 'Key revoked successfully.']);
    }

    /**
     * GET /api/usage
     * Monthly usage summary per endpoint for the dashboard.
     */
    public function usage(Request $request)
    {
        $user      = $request->user();
        $limits    = $user->planLimits();
        $endpoints = ['scrape', 'pdf', 'email'];

        $usage = [];
        foreach ($endpoints as $ep) {
            $used  = $user->usageThisMonth($ep);
            $limit = $limits[$ep];

            $usage[$ep] = [
                'used'       => $used,
                'limit'      => $limit === PHP_INT_MAX ? 'unlimited' : $limit,
                'percent'    => $limit === PHP_INT_MAX ? 0 : (int) round($used / $limit * 100),
                'remaining'  => $limit === PHP_INT_MAX ? 'unlimited' : max(0, $limit - $used),
                'reset_date' => now()->endOfMonth()->toDateString(),
            ];
        }

        return response()->json([
            'plan'       => $user->plan,
            'usage'      => $usage,
            'period'     => now()->format('F Y'),
        ]);
    }

    /**
     * GET /api/usage/history
     * Last 30 days of usage grouped by day (for dashboard chart).
     */
    public function history(Request $request)
    {
        $logs = UsageLog::where('user_id', $request->user()->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, endpoint, COUNT(*) as count')
            ->groupBy('date', 'endpoint')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $logs]);
    }
}