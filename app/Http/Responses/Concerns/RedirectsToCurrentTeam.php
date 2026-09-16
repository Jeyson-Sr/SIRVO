<?php

namespace App\Http\Responses\Concerns;

use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

trait RedirectsToCurrentTeam
{
    protected function redirectPathForCurrentTeam(Request $request, string $redirect): string
    {
        $team = $this->currentTeam($request);

        URL::defaults(['current_team' => $team->slug]);

        return "/{$team->slug}{$redirect}";
    }

    protected function toIntendedTeamRedirect(Request $request, string $redirect, int $jsonStatus = 200): Response
    {
        $home = $this->redirectPathForCurrentTeam($request, $redirect);

        if ($request->header('X-Inertia') || ! $request->wantsJson()) {
            return redirect()->intended($home);
        }

        return new JsonResponse(['two_factor' => false], $jsonStatus);
    }

    protected function currentTeam(Request $request): Team
    {
        $user = $request->user();

        abort_if(! $user, 403);

        $team = $user->currentTeam ?? $user->personalTeam();

        abort_if(! $team, 403);

        return $team;
    }
}
