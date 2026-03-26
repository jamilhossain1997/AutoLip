<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id', 'name', 'key', 'prefix', 'is_active', 'last_used_at',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usageLogs()
    {
        return $this->hasMany(UsageLog::class);
    }

    // ── Key generation ─────────────────────────────────────────────

    /**
     * Generate a new API key for a user.
     *
     * The raw key is returned ONCE and never stored in plain text.
     * Only the SHA-256 hash is saved in the DB.
     *
     * Usage:
     *   ['raw' => $raw, 'model' => $key] = ApiKey::generate($user, 'My Key');
     *   // Show $raw to the user. Store nothing.
     */
    public static function generate(User $user, string $name = 'Default Key'): array
    {
        $raw    = 'al_' . Str::random(37);  // "al_" prefix makes keys identifiable
        $prefix = substr($raw, 0, 8);       // shown in dashboard: "al_xK9mP"

        $apiKey = self::create([
            'user_id' => $user->id,
            'name'    => $name,
            'key'     => hash('sha256', $raw),
            'prefix'  => $prefix,
        ]);

        return ['raw' => $raw, 'model' => $apiKey];
    }

    /**
     * Find an active API key record by the raw bearer token.
     * Called on every API request by the ValidateApiKey middleware.
     */
    public static function findByRaw(string $raw): ?self
    {
        return self::where('key', hash('sha256', $raw))
            ->where('is_active', true)
            ->with('user')          // eager-load user to avoid extra query
            ->first();
    }
}