<?php

use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\GedcomController as ApiGedcomController;
use App\Http\Controllers\Api\V1\GlobalTreeController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\PersonController;
use App\Http\Controllers\Api\V1\PhotoRequestController;
use App\Http\Controllers\Api\V1\ProfileClaimController;
use App\Http\Controllers\Api\V1\ProfileDiscussionController;
use App\Http\Controllers\Api\V1\ProfileWatchController;
use App\Http\Controllers\Api\V1\RelationshipController;
use App\Http\Controllers\Api\V1\Settings\ApiTokenController;
use App\Http\Controllers\Api\V1\Settings\ProfileController;
use App\Http\Controllers\Api\V1\Settings\TwoFactorController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\DSARController;
use App\Http\Controllers\GedcomController;
use Illuminate\Support\Facades\Route;

// Public, unauthenticated health probe — used by the React SPA to confirm API
// reachability + CORS, and as a lightweight uptime endpoint.
Route::get('v1/health', fn () => response()->json([
    'status' => 'ok',
    'app' => config('app.name'),
    'time' => now()->toIso8601String(),
]))->name('api.v1.health');

// Public media streaming, protected by a temporary signed URL so the SPA can use
// it directly as an <img> src (no bearer token is sent on image requests).
Route::get('media/{mediaItem}/file', [MediaController::class, 'signedFile'])
    ->middleware('signed')
    ->name('api.media.file');

// Token (bearer) auth for the SPA — public endpoints, tightly throttled.
Route::prefix('v1/auth')->name('api.v1.auth.')->middleware('throttle:6,1')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])->name('two-factor-challenge');
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
});

// Public REST API — versioned, Sanctum-authenticated, rate-limited
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api'])
    ->name('api.v1.')
    ->group(function (): void {

        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::get('me/stats', [AuthController::class, 'stats'])->name('me.stats');
        Route::get('me/onboarding', [AuthController::class, 'onboarding'])->name('me.onboarding');

        // Trees
        Route::get('trees', [TreeController::class, 'index'])->name('trees.index');
        Route::post('trees', [TreeController::class, 'store'])->name('trees.store');
        Route::get('trees/{tree}', [TreeController::class, 'show'])->name('trees.show');
        Route::patch('trees/{tree}', [TreeController::class, 'update'])->name('trees.update');
        Route::get('trees/{tree}/people', [TreeController::class, 'people'])->name('trees.people.index');
        Route::get('trees/{tree}/graph', [TreeController::class, 'graph'])->name('trees.graph');
        Route::get('trees/{tree}/members', [TreeController::class, 'members'])->name('trees.members');

        // People (write ops scoped to a tree; reads + mutations by person id)
        Route::get('people/search', [PersonController::class, 'search'])->name('people.search');
        Route::post('trees/{tree}/people', [PersonController::class, 'store'])->name('trees.people.store');
        Route::post('trees/{tree}/people/relative', [PersonController::class, 'storeRelative'])->name('trees.people.relative');
        Route::get('people/{person}', [PersonController::class, 'show'])->name('people.show');
        Route::get('people/{person}/profile', [PersonController::class, 'profile'])->name('people.profile');
        Route::patch('people/{person}', [PersonController::class, 'update'])->name('people.update');
        Route::delete('people/{person}', [PersonController::class, 'destroy'])->name('people.destroy');
        Route::get('people/{person}/relationships', [PersonController::class, 'relationships'])->name('people.relationships');

        // Relationships
        Route::post('trees/{tree}/relationships', [RelationshipController::class, 'store'])->name('trees.relationships.store');
        Route::patch('trees/{tree}/relationships/{relationship}', [RelationshipController::class, 'update'])->name('trees.relationships.update');
        Route::delete('trees/{tree}/relationships/{relationship}', [RelationshipController::class, 'destroy'])->name('trees.relationships.destroy');

        // GEDCOM import (async) + export
        Route::post('gedcom/import', [ApiGedcomController::class, 'importNew'])->name('gedcom.import');
        Route::get('gedcom/import/{importId}', [ApiGedcomController::class, 'progress'])->name('gedcom.import.progress');
        Route::post('trees/{tree}/gedcom/import', [ApiGedcomController::class, 'import'])->name('trees.gedcom.import');
        Route::get('trees/{tree}/gedcom/export', [ApiGedcomController::class, 'export'])->name('trees.gedcom.export');

        // Media library
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::get('media/{mediaItem}', [MediaController::class, 'show'])->name('media.show');
        Route::delete('media/{mediaItem}', [MediaController::class, 'destroy'])->name('media.destroy');
        Route::get('trees/{tree}/media', [MediaController::class, 'treeIndex'])->name('trees.media.index');
        Route::post('trees/{tree}/media', [MediaController::class, 'store'])->name('trees.media.store');

        // Global tree
        Route::post('global-tree/relationship', [GlobalTreeController::class, 'relationshipPath'])->name('global-tree.relationship');

        // Account settings
        Route::patch('settings/profile', [ProfileController::class, 'update'])->name('settings.profile');
        Route::put('settings/password', [ProfileController::class, 'updatePassword'])->name('settings.password');
        Route::delete('settings/account', [ProfileController::class, 'destroy'])->name('settings.account');
        Route::get('settings/data-export', [DSARController::class, 'export'])->name('settings.data-export');

        Route::get('settings/two-factor', [TwoFactorController::class, 'show'])->name('settings.2fa.show');
        Route::post('settings/two-factor', [TwoFactorController::class, 'store'])->name('settings.2fa.store');
        Route::post('settings/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('settings.2fa.confirm');
        Route::post('settings/two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('settings.2fa.recovery');
        Route::delete('settings/two-factor', [TwoFactorController::class, 'destroy'])->name('settings.2fa.destroy');

        Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('settings.api-tokens.index');
        Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])->name('settings.api-tokens.store');
        Route::delete('settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('settings.api-tokens.destroy');

        // Profile interactions (Global Tree community features)
        Route::get('watch-list', [ProfileWatchController::class, 'index'])->name('watch-list');
        Route::post('people/{person}/watch', [ProfileWatchController::class, 'toggle'])->name('people.watch');
        Route::get('claims', [ProfileClaimController::class, 'index'])->name('claims.index');
        Route::post('people/{person}/claims', [ProfileClaimController::class, 'store'])->name('people.claims.store');
        Route::patch('claims/{claim}/review', [ProfileClaimController::class, 'review'])->name('claims.review');
        Route::get('people/{person}/discussions', [ProfileDiscussionController::class, 'index'])->name('people.discussions.index');
        Route::post('people/{person}/discussions', [ProfileDiscussionController::class, 'store'])->name('people.discussions.store');
        Route::delete('discussions/{discussion}', [ProfileDiscussionController::class, 'destroy'])->name('discussions.destroy');
        Route::post('people/{person}/photo-requests', [PhotoRequestController::class, 'store'])->name('people.photo-requests.store');
        Route::patch('photo-requests/{photoRequest}', [PhotoRequestController::class, 'update'])->name('photo-requests.update');

        // Site administration
        Route::prefix('admin')->name('admin.')->middleware('super.admin')->group(function (): void {
            Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
            Route::get('users', [AdminController::class, 'users'])->name('users');
            Route::patch('users/{user}/role', [AdminController::class, 'updateUserRole'])->name('users.role');
            Route::delete('users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
            Route::get('trees', [AdminController::class, 'trees'])->name('trees');
            Route::delete('trees/{tree}', [AdminController::class, 'deleteTree'])->name('trees.delete');
            Route::patch('trees/{tree}/global', [AdminController::class, 'toggleGlobalTree'])->name('trees.global');
            Route::get('activity', [AdminController::class, 'activity'])->name('activity');
        });

        // Gramps / desktop GEDCOM sync (legacy reader)
        Route::get('sync/gedcom/{tree}', [GedcomController::class, 'export'])->name('sync.gedcom.export');
        Route::post('sync/gedcom/{tree}', [GedcomController::class, 'import'])->name('sync.gedcom.import');
    });
