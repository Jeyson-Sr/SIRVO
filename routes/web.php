<?php

use App\Modules\Platform\DashboardController;
use App\Modules\Platform\HomeController;
use App\Modules\Teams\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

require app_path('Modules/Oee/routes.php');
require app_path('Modules/Teams/routes.php');
require app_path('Modules/Settings/routes.php');
