<?php

namespace App\Actions\Fortify;

use App\Actions\Teams\CreateTeam;
use App\Actions\Teams\JoinPlantTeam;
use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Rules\AllowedEmailDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(
        private CreateTeam $createTeam,
        private JoinPlantTeam $joinPlantTeam,
    ) {
        //
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'email' => [...$this->emailRules(), new AllowedEmailDomain],
            'password' => $this->passwordRules(),
        ], [
            'email.unique' => 'Este correo ya está registrado.',
        ], [
            'name' => 'nombre',
            'email' => 'correo',
            'password' => 'contraseña',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);
            $this->joinPlantTeam->handle($user);

            return $user;
        });
    }
}
