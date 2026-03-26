<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_key_id')->constrained()->onDelete('cascade');
            $table->enum('endpoint', ['scrape', 'pdf', 'email']);  // which feature
            $table->string('ip_address', 45)->nullable();
            $table->integer('response_ms')->nullable();             // response time in ms
            $table->smallInteger('status_code')->default(200);
            $table->json('meta')->nullable();                       // url scraped, email recipient, etc.
            $table->timestamp('created_at');                        // no updated_at — logs are immutable

            // Composite indexes for fast monthly usage queries
            $table->index(['user_id', 'endpoint', 'created_at']);
            $table->index(['api_key_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_logs');
    }
};
