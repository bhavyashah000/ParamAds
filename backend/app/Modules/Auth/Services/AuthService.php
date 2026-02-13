<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Organizations\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Register a new user and create their organization.
     */
    public function register(array $data): array
    {
        $organization = Organization::create([
            'name' => $data['organization_name'] ?? $data['name'] . "'s Organization",
            'slug' => Str::slug($data['organization_name'] ?? $data['name']) . '-' . Str::random(6),
            'email' => $data['email'],
            'timezone' => $data['timezone'] ?? 'UTC',
            'currency' => $data['currency'] ?? 'USD',
            'plan' => 'free',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'organization_id' => $organization->id,
            'role' => 'owner',
            'timezone' => $data['timezone'] ?? 'UTC',
            'is_active' => true,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('organization'),
            'token' => $token,
            'organization' => $organization,
        ];
    }

    /**
     * Authenticate user and return token.
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('organization'),
            'token' => $token,
        ];
    }

    /**
     * Logout user (revoke current token).
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Invite a team member to the organization.
     */
    public function inviteTeamMember(Organization $organization, array $data): User
    {
        $tempPassword = Str::random(16);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($tempPassword),
            'organization_id' => $organization->id,
            'role' => $data['role'] ?? 'member',
            'is_active' => true,
        ]);

        // TODO: Send invitation email with temp password

        return $user;
    }

    /**
     * Update user profile.
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update(array_filter($data, fn($v) => !is_null($v)));
        return $user->fresh();
    }

    /**
     * Change user password.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);
    }
}
