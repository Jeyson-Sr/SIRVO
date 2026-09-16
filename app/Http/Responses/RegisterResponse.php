<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    use RedirectsToCurrentTeam;

    public function toResponse($request): Response
    {
        return $this->toIntendedTeamRedirect($request, Fortify::redirects('register'), 201);
    }
}
