<?php

namespace App\Http\Controllers;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use RedirectsToCurrentTeam;

    public function __invoke(Request $request): RedirectResponse
    {
        if ($request->user() === null) {
            return redirect()->route('login');
        }

        return redirect()->to($this->redirectPathForCurrentTeam($request, '/dashboard'));
    }
}
