<?php

namespace App\Modules\Organizations\Services;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Support\Str;

class OrganizationService
{
    /**
     * Get organization with related data.
     */
    public function getOrganization(int $organizationId): Organization
    {
        return Organization::with(['users', 'adAccounts'])
            ->findOrFail($organizationId);
    }

    /**
     * Update organization settings.
     */
    public function update(Organization $organization, array $data): Organization
    {
        $organization->update(array_filter($data, fn($v) => !is_null($v)));
        return $organization->fresh();
    }

    /**
     * Update organization settings JSON.
     */
    public function updateSettings(Organization $organization, array $settings): Organization
    {
        $currentSettings = $organization->settings ?? [];
        $organization->update([
            'settings' => array_merge($currentSettings, $settings),
        ]);
        return $organization->fresh();
    }

    /**
     * Get team members.
     */
    public function getTeamMembers(Organization $organization)
    {
        return $organization->users()->active()->get();
    }

    /**
     * Update team member role.
     */
    public function updateMemberRole(Organization $organization, int $userId, string $role): User
    {
        $user = $organization->users()->findOrFail($userId);
        $user->update(['role' => $role]);
        return $user->fresh();
    }

    /**
     * Remove team member.
     */
    public function removeMember(Organization $organization, int $userId): void
    {
        $user = $organization->users()->findOrFail($userId);

        if ($user->isOwner()) {
            throw new \Exception('Cannot remove the organization owner.');
        }

        $user->update(['is_active' => false]);
        $user->tokens()->delete();
    }

    /**
     * Get organization usage stats.
     */
    public function getUsageStats(Organization $organization): array
    {
        return [
            'ad_accounts' => $organization->adAccounts()->count(),
            'campaigns' => $organization->campaigns()->count(),
            'automation_rules' => $organization->automationRules()->count(),
            'team_members' => $organization->users()->active()->count(),
        ];
    }
}
