<?php

namespace Squareetlabs\LaravelTeamsPermissions\Support\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Squareetlabs\LaravelTeamsPermissions\Exceptions\AuditTableMissingException;
use Squareetlabs\LaravelTeamsPermissions\Models\TeamAuditLog;
use Squareetlabs\LaravelTeamsPermissions\Support\Facades\Teams as TeamsFacade;
use Exception;

/**
 * AuditService
 * 
 * Handles audit logging for team-related actions.
 * Logs actions to both database (TeamAuditLog) and configured log channel.
 * 
 * Only logs events that are enabled in configuration and validates that
 * the audit table exists before attempting to log.
 * 
 * @package Squareetlabs\LaravelTeamsPermissions\Support\Services
 */
class AuditService
{
    /**
     * Log an audit event.
     * 
     * Validates that audit is enabled and the event is in the allowed events list.
     * Creates a database record and logs to the configured channel.
     * 
     * Automatically captures IP address and user agent from the current request.
     *
     * @param string $action The action being logged (must be in config events list)
     * @param mixed $team The team the action relates to (can be Team model or ID)
     * @param mixed $user The user performing the action (can be User model or ID)
     * @param mixed|null $subject Optional subject entity (model) the action relates to
     * @param array|null $oldValues Optional old values before the change
     * @param array|null $newValues Optional new values after the change
     * @return void
     * @throws Exception If audit table is missing when audit is enabled
     */
    public function log(
        string $action,
        mixed $team,
        mixed $user,
        mixed $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        if (!Config::get('teams.audit.enabled')) {
            return;
        }

        // Verificar que la tabla de auditoría existe
        if (!Schema::hasTable('team_audit_logs')) {
            throw new AuditTableMissingException();
        }

        $events = Config::get('teams.audit.events');
        
        if (!in_array($action, $events, true)) {
            return;
        }

        $request = request();
        $ipAddress = $request?->ip();
        $userAgent = $request?->userAgent();

        $data = [
            'team_id' => is_object($team) ? $team->id : $team,
            'user_id' => is_object($user) ? $user->id : $user,
            'action' => $action,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ];

        if ($subject) {
            $data['subject_type'] = is_object($subject) ? get_class($subject) : null;
            $data['subject_id'] = is_object($subject) ? $subject->id : $subject;
        }

        if ($oldValues !== null) {
            $data['old_values'] = $oldValues;
        }

        if ($newValues !== null) {
            $data['new_values'] = $newValues;
        }

        TeamAuditLog::create($data);

        $logChannel = Config::get('teams.audit.log_channel');
        Log::channel($logChannel)->info("Team audit: {$action}", $data);
    }

    /**
     * Log role assignment.
     * 
     * Convenience method to log when a role is assigned to a user.
     *
     * @param mixed $team The team where the role was assigned
     * @param mixed $user The user who assigned the role
     * @param mixed $role The role that was assigned (can be Role model or ID)
     * @return void
     * @throws Exception If audit table is missing when audit is enabled
     */
    public function logRoleAssigned(mixed $team, mixed $user, mixed $role): void
    {
        $this->log('role_assigned', $team, $user, $role, null, [
            'role_id' => is_object($role) ? $role->id : $role,
            'role_code' => is_object($role) ? $role->code : null,
        ]);
    }

    /**
     * Log permission granted.
     * 
     * Convenience method to log when a permission is granted.
     *
     * @param mixed $team The team where the permission was granted
     * @param mixed $user The user who granted the permission
     * @param string $permission The permission code that was granted
     * @return void
     * @throws Exception If audit table is missing when audit is enabled
     */
    public function logPermissionGranted(mixed $team, mixed $user, string $permission): void
    {
        $this->log('permission_granted', $team, $user, null, null, ['permission' => $permission]);
    }

    /**
     * Log permission revoked.
     * 
     * Convenience method to log when a permission is revoked.
     *
     * @param mixed $team The team where the permission was revoked
     * @param mixed $user The user who revoked the permission
     * @param string $permission The permission code that was revoked
     * @return void
     * @throws Exception If audit table is missing when audit is enabled
     */
    public function logPermissionRevoked(mixed $team, mixed $user, string $permission): void
    {
        $this->log('permission_revoked', $team, $user, null, ['permission' => $permission], null);
    }

    /**
     * Log team member added.
     * 
     * Convenience method to log when a member is added to a team.
     *
     * @param mixed $team The team the member was added to
     * @param mixed $user The user who added the member
     * @param mixed $member The member that was added (can be User model or ID)
     * @param mixed $role The role assigned to the member (can be Role model or ID)
     * @return void
     * @throws Exception If audit table is missing when audit is enabled
     */
    public function logTeamMemberAdded(mixed $team, mixed $user, mixed $member, mixed $role): void
    {
        $this->log('team_member_added', $team, $user, $member, null, [
            'member_id' => is_object($member) ? $member->id : $member,
            'role_id' => is_object($role) ? $role->id : $role,
        ]);
    }

    /**
     * Log team member removed.
     * 
     * Convenience method to log when a member is removed from a team.
     *
     * @param mixed $team The team the member was removed from
     * @param mixed $user The user who removed the member
     * @param mixed $member The member that was removed (can be User model or ID)
     * @return void
     * @throws Exception If audit table is missing when audit is enabled
     */
    public function logTeamMemberRemoved(mixed $team, mixed $user, mixed $member): void
    {
        $this->log('team_member_removed', $team, $user, $member, [
            'member_id' => is_object($member) ? $member->id : $member,
        ], null);
    }
}

