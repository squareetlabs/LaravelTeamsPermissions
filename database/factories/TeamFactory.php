<?php

namespace Squareetlabs\LaravelTeamsPermissions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Squareetlabs\LaravelTeamsPermissions\Models\Team;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Squareetlabs\LaravelTeamsPermissions\Models\Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = TeamsFacade::model('user');
        
        return [
            'name' => $this->faker->company(),
            'user_id' => $userModel::factory(),
        ];
    }

    /**
     * Indicate that the team should have roles.
     *
     * @return static
     * @throws Exception
     */
    public function withRoles(): static
    {
        return $this->afterCreating(function (Team $team) {
            $team->addRole('admin', ['*'], 'Administrator');
            $team->addRole('member', ['posts.view', 'comments.create'], 'Member');
        });
    }

    /**
     * Indicate that the team should have groups.
     *
     * @return static
     * @throws Exception
     */
    public function withGroups(): static
    {
        return $this->afterCreating(function (Team $team) {
            $team->addGroup('moderators', ['posts.moderate', 'comments.moderate'], 'Moderators');
        });
    }
}

