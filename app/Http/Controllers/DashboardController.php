<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\UsageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Exceptions\IncompletePayment;

class DashboardController extends Controller
{
    // ── Overview ───────────────────────────────────────────────────

    public function index(Request $request)
    {
        $user = $request->user();

        // Monthly usage per endpoint
        $usage = $this->buildUsageSummary($user);

        // Last 30 days history for chart
        $history = $this->buildHistory($user);

        // API key count
        $keyCount = $user->apiKeys()->where('is_active', true)->count();
        $keyLimit = $this->keyLimit($user->plan);

        return view('dashboard.index', compact('usage', 'history', 'keyCount', 'keyLimit'));
    }

    // ── API Keys ───────────────────────────────────────────────────

    public function apiKeys(Request $request)
    {
        $user     = $request->user();
        $keys     = $user->apiKeys()->latest()->get();
        $keyLimit = $this->keyLimit($user->plan);

        return view('dashboard.api-keys', compact('keys', 'keyLimit'));
    }

    public function storeApiKey(Request $request)
    {
        $request->validate(['name' => 'required|string|max:64']);

        $user     = $request->user();
        $keyLimit = $this->keyLimit($user->plan);
        $current  = $user->apiKeys()->where('is_active', true)->count();

        if ($current >= $keyLimit) {
            return back()->with('error', "Your {$user->plan} plan allows {$keyLimit} active key(s). Upgrade to add more.");
        }

        ['raw' => $raw] = ApiKey::generate($user, $request->input('name'));

        return redirect()->route('api-keys.index')
            ->with('success', 'API key created.')
            ->with('new_key', $raw);       // shown once, not stored
    }

    public function destroyApiKey(Request $request, int $id)
    {
        $request->user()->apiKeys()->findOrFail($id)->update(['is_active' => false]);

        return back()->with('success', 'API key revoked.');
    }

    // ── Usage ──────────────────────────────────────────────────────

    public function usage(Request $request)
    {
        $user    = $request->user();
        $usage   = $this->buildUsageSummary($user);
        $history = $this->buildHistory($user);

        $logs = UsageLog::where('user_id', $user->id)
            ->latest('created_at')
            ->paginate(20);

        return view('dashboard.usage', compact('usage', 'history', 'logs'));
    }

    // ── Billing ────────────────────────────────────────────────────

    public function billing()
    {
        return view('dashboard.billing');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'price_id' => 'required|string',
            'plan'     => 'required|in:starter,pro,business',
        ]);

        $user = $request->user();

        try {
            return $user->newSubscription('default', $request->price_id)
                ->checkout([
                    'success_url' => route('dashboard') . '?upgraded=1',
                    'cancel_url'  => route('billing.index'),
                ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not start checkout: ' . $e->getMessage());
        }
    }

    public function billingPortal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('billing.index'));
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function buildUsageSummary($user): array
    {
        $limits = $user->planLimits();

        return collect(['scrape', 'pdf', 'email'])->mapWithKeys(function ($ep) use ($user, $limits) {
            $used  = $user->usageThisMonth($ep);
            $limit = $limits[$ep];

            return [$ep => [
                'used'    => $used,
                'limit'   => $limit,
                'percent' => $limit === PHP_INT_MAX ? 0 : ($limit > 0 ? (int) round($used / $limit * 100) : 0),
            ]];
        })->toArray();
    }

    private function buildHistory($user): array
    {
        return UsageLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, endpoint, COUNT(*) as count')
            ->groupBy('date', 'endpoint')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function keyLimit(string $plan): int
    {
        return match ($plan) {
            'starter'  => 1,
            'pro'      => 5,
            'business' => PHP_INT_MAX,
            default    => 1,
        };
    }
}