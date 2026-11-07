<?php

use App\Modules\Oee\OeeAccess;
use App\Modules\Oee\RefreshSqlDataCommand;
use App\Modules\Platform\ForbiddenInertiaResponse;
use App\Modules\Platform\HandleAppearance;
use App\Modules\Platform\HandleInertiaRequests;
use App\Modules\Teams\SetTeamUrlDefaults;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([RefreshSqlDataCommand::class])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();
            $team = $user?->currentTeam ?? $user?->personalTeam();

            if ($team === null) {
                return route('login');
            }

            if ($user !== null && OeeAccess::viewPanel($user, $team)) {
                return route('oee.dashboard', $team);
            }

            return route('dashboard', $team);
        });

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetTeamUrlDefaults::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($response->getStatusCode() !== 403 || ! ForbiddenInertiaResponse::shouldRender($request)) {
                return $response;
            }

            return ForbiddenInertiaResponse::toResponse($request);
        });
    })->create();
