<?php

namespace Squareetlabs\LaravelTeamsPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Squareetlabs\LaravelTeamsPermissions\Traits\HasMembers;

/**
 * Team Model
 * 
 * Represents a team in the system. Teams have:
 * - An owner (user who created the team)
 * - Members (users belonging to the team)
 * - Roles (with associated permissions)
 * - Groups (with associated permissions)
 * - Abilities (entity-specific permissions)
 * - Invitations (pending team invitations)
 * 
 * Team owners automatically have all permissions.
 * 
 * @package Squareetlabs\LaravelTeamsPermissions\Models
 * @property int|string $id
 * @property int|string $user_id The owner's user ID
 * @property string $name Team name
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Team extends Model
{
    use HasMembers;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['user_id', 'name'];

    /**
     * The relationships that should always be loaded.
     * 
     * Automatically eager loads roles with permissions and groups with permissions
     * to optimize queries.
     *
     * @var array<string>
     */
    protected $with = ['roles.permissions', 'groups.permissions'];

    /**
     * Creates a new instance of the model.
     * 
     * Configures the table name and primary key type based on configuration.
     *
     * @param array $attributes Model attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = Config::get('teams.tables.teams');

        if (Config::get('teams.primary_key.type') === 'uuid') {
            $this->keyType = 'string';
            $this->incrementing = false;
        }
    }
}
