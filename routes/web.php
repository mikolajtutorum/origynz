<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DSARController;
use App\Http\Controllers\FamilyEventController;
use App\Http\Controllers\FamilyEventSettingsController;
use App\Http\Controllers\FamilyStatisticsController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\GlobalTreeController;
use App\Http\Controllers\GlobalTreeSettingsController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\FamilyTreeController;
use App\Http\Controllers\GedcomController;
use App\Http\Controllers\MediaItemController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\PersonGlobalExclusionController;
use App\Http\Controllers\PersonRelationshipController;
use App\Http\Controllers\SourceController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\TreeManagerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::post('/locale', [LocaleController::class, 'store'])->name('locale.store');

// Legal pages — public, no auth required
Route::get('/privacy-policy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/terms-of-service', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/data-processing-agreement', [LegalController::class, 'dpa'])->name('legal.dpa');
Route::get('/ccpa-opt-out', [LegalController::class, 'ccpa'])->name('legal.ccpa');
Route::post('/ccpa-opt-out', [LegalController::class, 'ccpaOptOut'])->name('legal.ccpa.store')->middleware('auth');
Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])->name('auth.social.callback');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('settings/data-export', [DSARController::class, 'index'])->name('settings.data-export');
    Route::post('settings/data-export', [DSARController::class, 'export'])->name('settings.data-export.store');
    Route::get('global-tree', [GlobalTreeController::class, 'index'])->name('global-tree.index');
    Route::get('global-tree/pedigree', [GlobalTreeController::class, 'pedigree'])->name('global-tree.pedigree');
    Route::get('global-tree/pedigree/person/{person}', [GlobalTreeController::class, 'pedigreePerson'])->name('global-tree.pedigree.person');
    Route::patch('trees/{tree}/global-tree-settings', [GlobalTreeSettingsController::class, 'update'])->name('trees.global-tree-settings.update');
    Route::patch('people/{person}/global-tree-exclusion', [PersonGlobalExclusionController::class, 'update'])->name('people.global-tree-exclusion.update');
    Route::get('family-events', [FamilyEventController::class, 'index'])->name('family-events.index');
    Route::get('family-statistics', FamilyStatisticsController::class)->name('family-statistics.index');
    Route::get('media-library', [MediaItemController::class, 'globalIndex'])->name('media.index');
    Route::get('sites/{site}/open', [SiteController::class, 'open'])->name('sites.open');
    Route::get('trees/my', [FamilyTreeController::class, 'openFirst'])->name('trees.first');
    Route::get('trees/manage', [FamilyTreeController::class, 'manage'])->name('trees.manage');
    Route::get('trees/import', [FamilyTreeController::class, 'importPage'])->name('trees.import.index');
    Route::get('trees/{tree}/events/settings', [FamilyEventSettingsController::class, 'edit'])->name('trees.events.settings.edit');
    Route::patch('trees/{tree}/events/settings', [FamilyEventSettingsController::class, 'update'])->name('trees.events.settings.update');
    Route::post('trees/import', [GedcomController::class, 'importFromPage'])->name('trees.import.store');
    Route::get('gedcom/import/{importId}/progress', [GedcomController::class, 'importProgress'])->name('gedcom.import.progress');
    Route::get('gedcom/import/{importId}/complete', [GedcomController::class, 'completeImport'])->name('gedcom.import.complete');
    Route::get('trees/{tree}/managers', [TreeManagerController::class, 'show'])->name('trees.managers.show');
    Route::post('trees/{tree}/managers/invitations', [TreeManagerController::class, 'storeInvite'])->name('trees.managers.invitations.store');
    Route::patch('trees/{tree}/managers/requests/{membershipRequest}', [TreeManagerController::class, 'reviewRequest'])->name('trees.managers.requests.review');
    Route::post('trees', [FamilyTreeController::class, 'store'])->name('trees.store');
    Route::get('trees/{tree}', [FamilyTreeController::class, 'show'])->name('trees.show');
    Route::get('trees/{tree}/media-library', [MediaItemController::class, 'index'])->name('trees.media.index');
    Route::post('trees/{tree}/owner-person', [FamilyTreeController::class, 'assignOwnerPerson'])->name('trees.owner-person');
    Route::get('trees/{tree}/gedcom/export', [GedcomController::class, 'export'])->name('trees.gedcom.export');
    Route::post('trees/{tree}/gedcom/import', [GedcomController::class, 'import'])->name('trees.gedcom.import');
    Route::post('trees/{tree}/media', [MediaItemController::class, 'store'])->name('trees.media.store');
    Route::post('trees/{tree}/sources', [SourceController::class, 'store'])->name('trees.sources.store');
    Route::post('trees/{tree}/people', [PersonController::class, 'store'])->name('trees.people.store');
    Route::post('trees/{tree}/people/relative', [PersonController::class, 'storeRelative'])->name('trees.people.store-relative');
    Route::post('trees/{tree}/relationships', [PersonRelationshipController::class, 'store'])->name('trees.relationships.store');
    Route::patch('trees/{tree}/relationships/{relationship}', [PersonRelationshipController::class, 'update'])->name('trees.relationships.update');
    Route::patch('people/{person}', [PersonController::class, 'update'])->name('people.update');
    Route::get('media/{mediaItem}', [MediaItemController::class, 'show'])->name('media.show');
    Route::get('media/{mediaItem}/download', [MediaItemController::class, 'download'])->name('media.download');
    Route::get('media/{mediaItem}/preview', [MediaItemController::class, 'preview'])->name('media.preview');
});

require __DIR__.'/settings.php';
