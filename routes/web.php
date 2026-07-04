<?php

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
