<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FamilyTreeController;
use App\Http\Controllers\Admin\GlobalTreeController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'super.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/trees', [FamilyTreeController::class, 'index'])->name('trees.index');
    Route::get('/trees/{tree}', [FamilyTreeController::class, 'show'])->name('trees.show');
    Route::delete('/trees/{tree}', [FamilyTreeController::class, 'destroy'])->name('trees.destroy');

    Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity.index');

    Route::get('/global-tree', [GlobalTreeController::class, 'index'])->name('global-tree.index');
    Route::patch('/global-tree/{tree}/toggle', [GlobalTreeController::class, 'toggle'])->name('global-tree.toggle');
});
