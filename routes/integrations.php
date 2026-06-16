<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\DnaKitController;
use App\Http\Controllers\ExternalMemorialController;
use App\Http\Controllers\FamilySearchController;
use App\Http\Controllers\GeniController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\PhotoRequestController;
use App\Http\Controllers\WikiTreeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function (): void {

    // Integrations hub
    Route::get('integrations', [IntegrationsController::class, 'index'])->name('integrations.index');

    // FamilySearch
    Route::get('integrations/familysearch', [FamilySearchController::class, 'index'])->name('integrations.familysearch');
    Route::get('integrations/familysearch/redirect', [FamilySearchController::class, 'redirect'])->name('integrations.familysearch.redirect');
    Route::get('integrations/familysearch/callback', [FamilySearchController::class, 'callback'])->name('integrations.familysearch.callback');
    Route::delete('integrations/familysearch', [FamilySearchController::class, 'disconnect'])->name('integrations.familysearch.disconnect');
    Route::get('integrations/familysearch/search-person/{person}', [FamilySearchController::class, 'searchPerson'])->name('integrations.familysearch.search-person');
    Route::post('integrations/familysearch/import/{person}', [FamilySearchController::class, 'importPerson'])->name('integrations.familysearch.import-person');

    // WikiTree
    Route::get('integrations/wikitree', [WikiTreeController::class, 'index'])->name('integrations.wikitree');
    Route::post('integrations/wikitree/connect', [WikiTreeController::class, 'connect'])->name('integrations.wikitree.connect');
    Route::delete('integrations/wikitree', [WikiTreeController::class, 'disconnect'])->name('integrations.wikitree.disconnect');
    Route::post('integrations/wikitree/import/{person}', [WikiTreeController::class, 'importPerson'])->name('integrations.wikitree.import-person');

    // Geni
    Route::get('integrations/geni', [GeniController::class, 'index'])->name('integrations.geni');
    Route::get('integrations/geni/redirect', [GeniController::class, 'redirect'])->name('integrations.geni.redirect');
    Route::get('integrations/geni/callback', [GeniController::class, 'callback'])->name('integrations.geni.callback');
    Route::delete('integrations/geni', [GeniController::class, 'disconnect'])->name('integrations.geni.disconnect');
    Route::post('integrations/geni/import/{person}', [GeniController::class, 'importPerson'])->name('integrations.geni.import-person');

    // DNA kits
    Route::get('integrations/dna', [DnaKitController::class, 'index'])->name('integrations.dna.index');
    Route::get('integrations/dna/{kit}', [DnaKitController::class, 'show'])->name('integrations.dna.show');
    Route::post('integrations/dna', [DnaKitController::class, 'store'])->name('integrations.dna.store');
    Route::delete('integrations/dna/{kit}', [DnaKitController::class, 'destroy'])->name('integrations.dna.destroy');

    // External memorial IDs (per-person)
    Route::patch('people/{person}/external-memorials', [ExternalMemorialController::class, 'update'])->name('people.external-memorials.update');

    // Photo requests
    Route::post('people/{person}/photo-requests', [PhotoRequestController::class, 'store'])->name('people.photo-requests.store');
    Route::patch('photo-requests/{photoRequest}', [PhotoRequestController::class, 'update'])->name('people.photo-requests.update');

    // API token management
    Route::get('settings/api-tokens', [ApiTokenController::class, 'index'])->name('settings.api-tokens');
    Route::post('settings/api-tokens', [ApiTokenController::class, 'store'])->name('settings.api-tokens.store');
    Route::delete('settings/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('settings.api-tokens.destroy');
});
