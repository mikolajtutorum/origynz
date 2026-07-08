<?php

use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'api' => url('/api/v1'),
        'health' => url('/api/v1/health'),
        'frontend' => config('app.frontend_url'),
    ]);
})->name('home');

Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('auth.social.callback');

// OAuth callback for third-party genealogy integrations (identity carried in the
// signed `state`, so no session/auth middleware is needed here).
Route::get('/integrations/{provider}/callback', [IntegrationController::class, 'callback'])
    ->name('integrations.callback');
