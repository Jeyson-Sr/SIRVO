<?php

use App\Modules\Teams\EnsureTeamMembership;
use App\Modules\Teams\TeamController;
use App\Modules\Teams\TeamInvitationController;
use App\Modules\Teams\TeamMemberController;
use App\Modules\Teams\TeamUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

// Plant user administration. Route names keep the `oee.admin.` prefix that the
// frontend and the permission checks already rely on.
Route::prefix('{current_team}/admin')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->name('oee.admin.')
    ->group(function () {
        Route::get('users', [TeamUserController::class, 'index'])->name('users.index');
        Route::get('users/create', [TeamUserController::class, 'create'])->name('users.create');
        Route::post('users', [TeamUserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [TeamUserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [TeamUserController::class, 'destroy'])->name('users.destroy');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::post('settings/teams', [TeamController::class, 'store'])->name('teams.store');

    Route::middleware(EnsureTeamMembership::class)->group(function () {
        Route::get('settings/teams/{team}', [TeamController::class, 'edit'])->name('teams.edit');
        Route::patch('settings/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
        Route::delete('settings/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
        Route::post('settings/teams/{team}/switch', [TeamController::class, 'switch'])->name('teams.switch');
        Route::delete('settings/teams/{team}/leave', [TeamController::class, 'leave'])->name('teams.leave');

        Route::patch('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'update'])->name('teams.members.update');
        Route::delete('settings/teams/{team}/members/{user}', [TeamMemberController::class, 'destroy'])->name('teams.members.destroy');

        Route::post('settings/teams/{team}/invitations', [TeamInvitationController::class, 'store'])->name('teams.invitations.store');
        Route::delete('settings/teams/{team}/invitations/{invitation}', [TeamInvitationController::class, 'destroy'])->name('teams.invitations.destroy');
    });
});
