<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    use RedirectsToCurrentTeam;

    public function toResponse($request): Response
    {
        return $this->toIntendedTeamRedirect($request, Fortify::redirects('login'));
    }
}
