<?php

use App\Http\Controllers\Teams\TeamUserController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Modules\Oee\Http\Controllers\OeeDashboardController;
use App\Modules\Oee\Http\Controllers\ProductionController;
use App\Modules\Oee\Http\Controllers\SkuAdminController;
use App\Modules\Oee\Http\Controllers\SkuController;
use App\Modules\Oee\Http\Controllers\StopCodeAdminController;
use App\Modules\Oee\Http\Controllers\StopCodeController;
use Illuminate\Support\Facades\Route;

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->name('oee.')
    ->group(function () {
        Route::get('oee', OeeDashboardController::class)->name('dashboard');

        // Scoped so a production can only be reached through the team that owns it.
        Route::prefix('production')->name('productions.')->scopeBindings()->group(function () {
            Route::get('/', [ProductionController::class, 'index'])->name('index');
            Route::get('record', [ProductionController::class, 'create'])->name('create');
            Route::post('/', [ProductionController::class, 'store'])->name('store');
            Route::get('{production}', [ProductionController::class, 'show'])->name('show');
            Route::get('{production}/edit', [ProductionController::class, 'edit'])->name('edit');
            Route::put('{production}', [ProductionController::class, 'update'])->name('update');
            Route::delete('{production}', [ProductionController::class, 'destroy'])->name('destroy');
            Route::post('{production}/close', [ProductionController::class, 'close'])->name('close');
            Route::post('{production}/reopen', [ProductionController::class, 'reopen'])->name('reopen');
        });

        Route::get('stop-codes', [StopCodeController::class, 'index'])->name('stop-codes.index');
        Route::get('skus', [SkuController::class, 'index'])->name('skus.index');

        Route::prefix('admin')->name('admin.')->group(function () {
            Route::resource('stop-codes', StopCodeAdminController::class)
                ->parameters(['stop-codes' => 'codStop'])
                ->except(['show']);

            Route::resource('skus', SkuAdminController::class)
                ->parameters(['skus' => 'oeeSku'])
                ->except(['show']);

            Route::get('users', [TeamUserController::class, 'index'])->name('users.index');
            Route::get('users/create', [TeamUserController::class, 'create'])->name('users.create');
            Route::post('users', [TeamUserController::class, 'store'])->name('users.store');
            Route::put('users/{user}', [TeamUserController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [TeamUserController::class, 'destroy'])->name('users.destroy');
        });
    });
