<?php

namespace App\Modules\Platform;

use App\Http\Controllers\Controller;
use App\Modules\Identity\User;
use App\Modules\Oee\OeeAccess;
use App\Modules\Teams\Team;
use App\Modules\Teams\TeamInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, Team $current_team): Response|RedirectResponse
    {
        $user = $request->user();

        if (OeeAccess::viewPanel($user, $current_team)) {
            return to_route('oee.dashboard', $current_team);
        }

        if (! $user->hasSection($current_team, AppSection::Dashboard)) {
            return $this->redirectToFirstSection($user, $current_team);
        }

        $email = strtolower($user->email);

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    /**
     * Send a user without dashboard access to the first section they may open.
     */
    private function redirectToFirstSection(User $user, Team $team): Response|RedirectResponse
    {
        $redirect = SectionCatalog::fallback($user, $team);

        if ($redirect !== null) {
            return $redirect;
        }

        return Inertia::render('dashboard', [
            'pendingInvitations' => [],
            'noAccess' => true,
        ]);
    }
}
