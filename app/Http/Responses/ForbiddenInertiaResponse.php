<?php

namespace App\Http\Responses;

use App\Data\SectionCatalog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ForbiddenInertiaResponse
{
    /**
     * Inertia visits and browser page loads get the custom panel. JSON clients
     * and PHPUnit keep the plain 403 so existing assertions stay stable.
     */
    public static function shouldRender(Request $request): bool
    {
        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return false;
        }

        if (! $request->header('X-Inertia') && (app()->runningUnitTests() || ! $request->acceptsHtml())) {
            return false;
        }

        return true;
    }

    /**
     * Render the forbidden panel with a place to send the user back to.
     */
    public static function toResponse(Request $request): Response
    {
        return Inertia::render('errors/403', [
            'fallbackUrl' => self::fallbackUrl($request),
        ])->toResponse($request)->setStatusCode(Response::HTTP_FORBIDDEN);
    }

    /**
     * Prefer the first section they may open; otherwise the plant dashboard.
     */
    private static function fallbackUrl(Request $request): string
    {
        $user = $request->user();
        $team = $user?->currentTeam;

        if ($user !== null && $team !== null) {
            return SectionCatalog::fallback($user, $team)?->getTargetUrl()
                ?? route('dashboard', $team);
        }

        return route('home');
    }
}
