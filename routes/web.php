<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

/* Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard'); */

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dashboard', fn () => auth()->check()
    ? redirect()->route('dashboard')
    : redirect()->route('login')
);
 
// ── Dashboard (auth required) ──────────────────────────────────────────────────
 
Route::middleware(['auth', 'verified'])->group(function () {
 
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
 
    Route::get('/dashboard/keys',            [DashboardController::class, 'apiKeys'])
        ->name('api-keys.index');
    Route::post('/dashboard/keys',           [DashboardController::class, 'storeApiKey'])
        ->name('api-keys.store');
    Route::delete('/dashboard/keys/{id}',    [DashboardController::class, 'destroyApiKey'])
        ->name('api-keys.destroy');
 
    Route::get('/dashboard/usage',           [DashboardController::class, 'usage'])
        ->name('usage.index');
 
    Route::get('/dashboard/billing',         [DashboardController::class, 'billing'])
        ->name('billing.index');
    Route::post('/dashboard/billing/checkout',[DashboardController::class, 'checkout'])
        ->name('billing.checkout');
    Route::post('/dashboard/billing/portal', [DashboardController::class, 'billingPortal'])
        ->name('billing.portal');
 
    Route::post('/stripe/webhook', '\Laravel\Cashier\Http\Controllers\WebhookController@handleWebhook')
        ->name('cashier.webhook');
});

require __DIR__.'/auth.php';
