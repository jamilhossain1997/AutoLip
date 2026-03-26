<?php

use App\Http\Controllers\Api\ApiKeyController;
use App\Http\Controllers\Api\EmailController;
use App\Http\Controllers\Api\PdfController;
use App\Http\Controllers\Api\ScrapeController;
use App\Http\Middleware\ValidateApiKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| AutoLib API Routes  —  routes/api.php
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api automatically by Laravel.
|
| Public routes need no auth.
| Feature routes are protected by ValidateApiKey middleware which handles:
|   - Authentication (Bearer token)
|   - Plan limit enforcement
|   - Usage logging
|
*/

// ── Public ─────────────────────────────────────────────────────────────

Route::get('/health', fn () => response()->json([
    'status'  => 'ok',
    'service' => 'AutoLib API',
    'version' => '1.0.0',
    'time'    => now()->toIso8601String(),
]));

// ── Feature endpoints (API key required) ───────────────────────────────

Route::post('/scrape', [ScrapeController::class, 'scrape'])
    ->middleware(ValidateApiKey::class . ':scrape');

Route::post('/pdf', [PdfController::class, 'generate'])
    ->middleware(ValidateApiKey::class . ':pdf');

Route::post('/email', [EmailController::class, 'send'])
    ->middleware(ValidateApiKey::class . ':email');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/keys',          [ApiKeyController::class, 'index']);
    Route::post('/keys',         [ApiKeyController::class, 'store']);
    Route::delete('/keys/{id}',  [ApiKeyController::class, 'destroy']);

    Route::get('/usage',         [ApiKeyController::class, 'usage']);
    Route::get('/usage/history', [ApiKeyController::class, 'history']);
});