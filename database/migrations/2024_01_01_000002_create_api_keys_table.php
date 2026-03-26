<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name')->default('Default Key');        // friendly label shown in dashboard
            $table->string('key', 64)->unique();                   // sha256 hash of the raw key
            $table->string('prefix', 8);                           // first 8 chars e.g. "al_xK9mP"
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['key', 'is_active']);                   // fast lookup on every API request
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
