<?php

namespace App\Http\Requests\Teams;

use App\Enums\AppSection;
use App\Enums\TeamRole;
use App\Models\Team;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamUserAccessRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $team = $this->route('current_team');

        return $team instanceof Team && ($this->user()?->can('manageUsers', $team) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sections' => ['nullable', 'array'],
            'sections.*' => ['string', Rule::in(AppSection::grantableValues())],
            'role' => ['nullable', 'string', Rule::in($this->assignableRoleValues())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sections' => 'secciones',
            'role' => 'rol',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function assignableRoleValues(): array
    {
        $team = $this->route('current_team');

        if (! $team instanceof Team) {
            return [];
        }

        return array_column(TeamRole::assignableBy($this->user()?->teamRole($team)), 'value');
    }
}
