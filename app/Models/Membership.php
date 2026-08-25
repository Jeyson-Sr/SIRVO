<?php

namespace App\Models;

use App\Enums\AppSection;
use App\Enums\TeamRole;
use BackedEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $user_id
 * @property TeamRole $role
 * @property array<int, string>|null $sections
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 * @property-read User $user
 */
#[Fillable(['team_id', 'user_id', 'role', 'sections'])]
class Membership extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'team_members';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Get the team that the membership belongs to.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user that belongs to this membership.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => TeamRole::class,
            'sections' => 'array',
        ];
    }

    /**
     * The sections this membership may open.
     *
     * Owners and administrators keep every section. Everyone else keeps
     * only the sections granted on the users panel.
     *
     * @return array<int, string>
     */
    public function grantedSections(): array
    {
        if ($this->role?->managesPlant() ?? false) {
            return AppSection::values();
        }

        if ($this->sections === null) {
            return AppSection::operatorDefaults();
        }

        return self::normalizeOperatorSections($this->sections);
    }

    /**
     * Keep only grantable sections. Panel OEE is a default, not a lock.
     *
     * @param  array<int, string>|null  $sections
     * @return array<int, string>
     */
    public static function normalizeOperatorSections(?array $sections): array
    {
        return array_values(array_intersect($sections ?? [], AppSection::grantableValues()));
    }

    /**
     * Determine whether this membership may open the given section.
     */
    public function hasSection(BackedEnum|string $section): bool
    {
        $value = $section instanceof BackedEnum ? $section->value : $section;

        return in_array($value, $this->grantedSections(), true);
    }
}
