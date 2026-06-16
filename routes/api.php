<?php

use App\Http\Controllers\Api\V1\DocumentationController;
use App\Http\Controllers\Api\V1\PersonController;
use App\Http\Controllers\Api\V1\TreeController;
use App\Http\Controllers\GedcomController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Legacy endpoint
Route::get('/user', fn (Request $request) => $request->user())
    ->middleware('auth:sanctum');

// Public REST API — versioned, Sanctum-authenticated, rate-limited
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:api'])
    ->name('api.v1.')
    ->group(function (): void {

        Route::get('me', fn (Request $request) => response()->json([
            'id'    => $request->user()->id,
            'name'  => $request->user()->name,
            'email' => $request->user()->email,
        ]))->name('me');

        // Trees
        Route::get('trees', [TreeController::class, 'index'])->name('trees.index');
        Route::get('trees/{tree}', [TreeController::class, 'show'])->name('trees.show');
        Route::get('trees/{tree}/people', [TreeController::class, 'people'])->name('trees.people.index');

        // People
        Route::get('people/search', [PersonController::class, 'search'])->name('people.search');
        Route::get('people/{person}', [PersonController::class, 'show'])->name('people.show');
        Route::get('people/{person}/relationships', [PersonController::class, 'relationships'])->name('people.relationships');

        // Gramps / desktop GEDCOM sync
        Route::get('sync/gedcom/{tree}', [GedcomController::class, 'export'])->name('sync.gedcom.export');
        Route::post('sync/gedcom/{tree}', [GedcomController::class, 'import'])->name('sync.gedcom.import');
    });
