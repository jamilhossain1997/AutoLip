<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsageLog extends Model
{
    // Logs are append-only — no updated_at needed
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'api_key_id', 'endpoint',
        'ip_address', 'response_ms', 'status_code', 'meta', 'created_at',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }
}