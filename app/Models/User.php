<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name', 'email', 'password', 'plan',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'trial_ends_at'     => 'datetime',
        'password'          => 'hashed',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(UsageLog::class);
    }

    
    public function planLimits(): array
    {
        return match ($this->plan) {
            'starter'  => ['scrape' => 500,          'pdf' => 100,  'email' => 1000],
            'pro'      => ['scrape' => 5000,          'pdf' => 500,  'email' => 10000],
            'business' => ['scrape' => PHP_INT_MAX,   'pdf' => 2000, 'email' => 50000],
            default    => ['scrape' => 50,            'pdf' => 10,   'email' => 100],   
        };
    }

    
    public function usageThisMonth(string $endpoint): int
    {
        return $this->usageLogs()
            ->where('endpoint', $endpoint)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    
    public function hasReachedLimit(string $endpoint): bool
    {
        $limit = $this->planLimits()[$endpoint] ?? 0;

        if ($limit === PHP_INT_MAX) {
            return false; 
        }

        return $this->usageThisMonth($endpoint) >= $limit;
    }
}