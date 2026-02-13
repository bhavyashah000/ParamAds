<?php

namespace App\Modules\Organizations\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Organizations\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    public function __construct(
        private OrganizationService $organizationService,
        private AuthService $authService
    ) {}

    /**
     * Get current organization.
     */
    public function show(Request $request): JsonResponse
    {
        $organization = $this->organizationService->getOrganization(
            $request->user()->organization_id
        );

        return response()->json(['data' => $organization]);
    }

    /**
     * Update organization.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:3',
        ]);

        $organization = $this->organizationService->update(
            $request->user()->organization,
            $request->only(['name', 'email', 'phone', 'website', 'timezone', 'currency'])
        );

        return response()->json([
            'message' => 'Organization updated.',
            'data' => $organization,
        ]);
    }

    /**
     * Update organization settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate(['settings' => 'required|array']);

        $organization = $this->organizationService->updateSettings(
            $request->user()->organization,
            $request->settings
        );

        return response()->json([
            'message' => 'Settings updated.',
            'data' => $organization,
        ]);
    }

    /**
     * Get team members.
     */
    public function teamMembers(Request $request): JsonResponse
    {
        $members = $this->organizationService->getTeamMembers(
            $request->user()->organization
        );

        return response()->json(['data' => $members]);
    }

    /**
     * Invite team member.
     */
    public function inviteMember(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:admin,manager,member,viewer',
        ]);

        $user = $this->authService->inviteTeamMember(
            $request->user()->organization,
            $request->only(['name', 'email', 'role'])
        );

        return response()->json([
            'message' => 'Team member invited.',
            'data' => $user,
        ], 201);
    }

    /**
     * Update member role.
     */
    public function updateMemberRole(Request $request, int $userId): JsonResponse
    {
        $request->validate(['role' => 'required|in:admin,manager,member,viewer']);

        $user = $this->organizationService->updateMemberRole(
            $request->user()->organization,
            $userId,
            $request->role
        );

        return response()->json([
            'message' => 'Member role updated.',
            'data' => $user,
        ]);
    }

    /**
     * Remove team member.
     */
    public function removeMember(Request $request, int $userId): JsonResponse
    {
        $this->organizationService->removeMember(
            $request->user()->organization,
            $userId
        );

        return response()->json(['message' => 'Member removed.']);
    }

    /**
     * Get usage stats.
     */
    public function usageStats(Request $request): JsonResponse
    {
        $stats = $this->organizationService->getUsageStats(
            $request->user()->organization
        );

        return response()->json(['data' => $stats]);
    }
}
