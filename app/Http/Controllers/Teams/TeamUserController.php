<?php

namespace App\Http\Controllers\Teams;

use App\Actions\Teams\CreateTeamUser;
use App\Actions\Teams\DeleteTeamUser;
use App\Actions\Teams\GrantSectionAccess;
use App\Enums\AppSection;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Teams\StoreTeamUserRequest;
use App\Http\Requests\Teams\UpdateTeamUserAccessRequest;
use App\Models\Membership;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TeamUserController extends Controller
{
    /**
     * Display the team members stored in the users table.
     */
    public function index(Request $request, DeleteTeamUser $deleteTeamUser, Team $current_team): Response
    {
        Gate::authorize('manageUsers', $current_team);

        $actor = $request->user();
        $actorRole = $actor->teamRole($current_team);
        $memberships = $current_team->memberships()->get()->keyBy('user_id');

        $users = User::query()
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($memberships, $actor, $current_team, $deleteTeamUser, $actorRole): array {
                $membership = $memberships->get($user->id);

                if ($membership === null) {
                    return $this->toUnaffiliatedItem($user, $actor, $actorRole);
                }

                $membership->setRelation('user', $user);

                return $this->toListItem($membership, $actor, $current_team, $deleteTeamUser, $actorRole);
            });

        return Inertia::render('oee/admin/users/index', [
            'users' => $users,
            'sections' => AppSection::grantableOptions(),
            'roles' => TeamRole::assignableBy($actorRole),
        ]);
    }

    /**
     * Show the form for creating a user in the users table.
     */
    public function create(Team $current_team): Response
    {
        Gate::authorize('manageUsers', $current_team);

        return Inertia::render('oee/admin/users/create', [
            'sections' => AppSection::grantableOptions(),
        ]);
    }

    /**
     * Store a newly created user and attach them to the team.
     */
    public function store(
        StoreTeamUserRequest $request,
        CreateTeamUser $createTeamUser,
        Team $current_team,
    ): RedirectResponse {
        Gate::authorize('manageUsers', $current_team);

        $createTeamUser->handle($current_team, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario creado.']);

        return to_route('oee.admin.users.index', $current_team);
    }

    /**
     * Grant or revoke the sections a user may open.
     */
    public function update(
        UpdateTeamUserAccessRequest $request,
        GrantSectionAccess $grantSectionAccess,
        Team $current_team,
        User $user,
    ): RedirectResponse {
        Gate::authorize('manageUsers', $current_team);

        $grantSectionAccess->handle(
            $current_team,
            $user,
            $request->validated('sections') ?? [],
            $request->user(),
            $request->validated('role'),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Accesos actualizados.']);

        return to_route('oee.admin.users.index', $current_team);
    }

    /**
     * Remove the user from the users table.
     */
    public function destroy(
        Request $request,
        DeleteTeamUser $deleteTeamUser,
        Team $current_team,
        User $user,
    ): RedirectResponse {
        Gate::authorize('manageUsers', $current_team);

        $deleteTeamUser->handle($current_team, $request->user(), $user);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario eliminado.']);

        return to_route('oee.admin.users.index', $current_team);
    }

    /**
     * @return array{id: int, name: string, email: string, isMember: bool, role: string, roleLabel: string, sections: array<int, string>, locked: bool, canDelete: bool, canChangeRole: bool}
     */
    private function toListItem(Membership $membership, User $actor, Team $team, DeleteTeamUser $deleteTeamUser, ?TeamRole $actorRole): array
    {
        $canChangeRole = $actorRole !== null
            && ! $actor->is($membership->user)
            && $actorRole->outranks($membership->role);

        return [
            'id' => $membership->user->id,
            'name' => $membership->user->name,
            'email' => $membership->user->email,
            'isMember' => true,
            'role' => $membership->role->value,
            'roleLabel' => $membership->role->label(),
            'sections' => $membership->grantedSections(),
            'locked' => $membership->role->isAtLeast(TeamRole::Admin),
            'canDelete' => $deleteTeamUser->authorized($actor, $membership->user, $team, $membership->role, $actorRole),
            'canChangeRole' => $canChangeRole,
        ];
    }

    /**
     * @return array{id: int, name: string, email: string, isMember: bool, role: string, roleLabel: string, sections: array<int, string>, locked: bool, canDelete: bool, canChangeRole: bool}
     */
    private function toUnaffiliatedItem(User $user, User $actor, ?TeamRole $actorRole): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'isMember' => false,
            'role' => '',
            'roleLabel' => 'Sin acceso',
            'sections' => AppSection::operatorDefaults(),
            'locked' => false,
            'canDelete' => $actorRole !== null && ! $actor->is($user),
            'canChangeRole' => false,
        ];
    }
}
