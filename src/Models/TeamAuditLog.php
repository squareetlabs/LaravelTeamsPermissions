<?php

namespace Squareetlabs\LaravelTeamsPermissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

class TeamAuditLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the team that owns the audit log.
     *
     * @return BelongsTo
     * @throws Exception
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(TeamsFacade::model('team'), 'team_id');
    }

    /**
     * Get the user that performed the action.
     *
     * @return BelongsTo
     * @throws Exception
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TeamsFacade::model('user'), 'user_id');
    }

    /**
     * Get the subject of the audit log.
     *
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}

