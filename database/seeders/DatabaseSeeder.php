<?php

namespace Database\Seeders;

use App\Enums\AppSection;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\Oee\CodStopSeeder;
use Database\Seeders\Oee\DemoProductionSeeder;
use Database\Seeders\Oee\SkuSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CodStopSeeder::class,
            SkuSeeder::class,
        ]);

        $plant = $this->plant();

        $owner = $this->plantUser(
            $plant,
            TeamRole::Owner,
            'Test User',
            'test@example.com',
        );

        $this->call(PlantAdminSeeder::class);

        $this->plantUser($plant, TeamRole::Admin, 'Ana Admin', 'admin@example.com');
        $this->plantUser($plant, TeamRole::Engineer, 'Luis Ingeniero', 'ingeniero@example.com');
        $this->plantUser($plant, TeamRole::Member, 'Pedro Operador', 'operador@example.com');

        $this->callWith(DemoProductionSeeder::class, [
            'team' => $plant,
            'creator' => $owner,
        ]);
    }

    /**
     * Get or create the plant team used by the demo.
     */
    private function plant(): Team
    {
        $plant = Team::query()->where('slug', 'planta-lima')->first();

        if ($plant instanceof Team) {
            return $plant;
        }

        return Team::query()->create([
            'name' => 'Planta Lima',
            'slug' => 'planta-lima',
            'is_personal' => false,
        ]);
    }

    /**
     * Create a demo user and give them a role on the plant.
     */
    private function plantUser(Team $plant, TeamRole $role, string $name, string $email): User
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => $email,
            ]);
        }

        $plant->memberships()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'role' => $role,
                'sections' => AppSection::values(),
            ],
        );

        $user->switchTeam($plant);

        return $user->fresh();
    }
}
