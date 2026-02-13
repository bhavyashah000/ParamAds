<?php

namespace App\Modules\Agency\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamService
{
    /**
     * Available roles and their permissions.
     */
    public const ROLES = [
        'owner' => [
            'manage_organization', 'manage_billing', 'manage_team',
            'manage_campaigns', 'manage_automations', 'manage_creatives',
            'manage_audiences', 'manage_reports', 'manage_webhooks',
            'view_all', 'delete_all',
        ],
        'admin' => [
            'manage_team', 'manage_campaigns', 'manage_automations',
            'manage_creatives', 'manage_audiences', 'manage_reports',
            'manage_webhooks', 'view_all',
        ],
        'manager' => [
            'manage_campaigns', 'manage_automations', 'manage_creatives',
            'manage_audiences', 'manage_reports', 'view_all',
        ],
        'analyst' => [
            'view_all', 'manage_reports',
        ],
        'viewer' => [
            'view_all',
        ],
    ];

    /**
     * Invite a team member.
     */
    public function invite(int $orgId, int $invitedBy, string $email, string $role): array
    {
        $token = Str::random(64);

        DB::table('team_invitations')->insert([
            'organization_id' => $orgId,
            'invited_by' => $invitedBy,
            'email' => $email,
            'role' => $role,
            'token' => $token,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // In production, send invitation email
        return [
            'email' => $email,
            'role' => $role,
            'token' => $token,
            'expires_at' => now()->addDays(7)->toIso8601String(),
        ];
    }

    /**
     * Accept an invitation.
     */
    public function acceptInvitation(string $token, User $user): bool
    {
        $invitation = DB::table('team_invitations')
            ->where('token', $token)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();

        if (!$invitation) {
            return false;
        }

        DB::transaction(function () use ($invitation, $user) {
            $user->update([
                'organization_id' => $invitation->organization_id,
                'role' => $invitation->role,
            ]);

            DB::table('team_invitations')
                ->where('id', $invitation->id)
                ->update(['status' => 'accepted', 'updated_at' => now()]);
        });

        return true;
    }

    /**
     * List team members.
     */
    public function listMembers(int $orgId): array
    {
        return User::where('organization_id', $orgId)
            ->select('id', 'name', 'email', 'role', 'created_at', 'last_login_at')
            ->orderBy('role')
            ->get()
            ->toArray();
    }

    /**
     * Update member role.
     */
    public function updateRole(int $orgId, int $userId, string $newRole): bool
    {
        return User::where('id', $userId)
            ->where('organization_id', $orgId)
            ->update(['role' => $newRole]) > 0;
    }

    /**
     * Remove member from organization.
     */
    public function removeMember(int $orgId, int $userId): bool
    {
        return User::where('id', $userId)
            ->where('organization_id', $orgId)
            ->where('role', '!=', 'owner')
            ->update(['organization_id' => null, 'role' => 'viewer']) > 0;
    }

    /**
     * Check if user has permission.
     */
    public function hasPermission(User $user, string $permission): bool
    {
        $rolePermissions = self::ROLES[$user->role] ?? [];
        return in_array($permission, $rolePermissions);
    }
}
