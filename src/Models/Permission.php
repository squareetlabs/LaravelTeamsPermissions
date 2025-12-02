<?php

namespace Squareetlabs\LaravelTeamsPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Config;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;

class Permission extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['name', 'code'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (Config::get('teams.primary_key.type') === 'uuid') {
            $this->keyType = 'string';
            $this->incrementing = false;
        }
    }

    /**
     * Get all the groups that are assigned this permission.
     */
    public function groups(): MorphToMany
    {
        return $this->morphedByMany(TeamsFacade::model('group'), 'entity', 'entity_permission');
    }

    /**
     * Get all the roles that are assigned this permission.
     */
    public function roles(): MorphToMany
    {
        return $this->morphedByMany(TeamsFacade::model('role'), 'entity', 'entity_permission');
    }
}
