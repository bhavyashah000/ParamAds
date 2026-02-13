<?php

namespace App\Modules\Agency\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ActivityLogService
{
    /**
     * Log an activity.
     */
    public function log(
        int $orgId,
        ?int $userId,
        string $action,
        string $resourceType,
        ?int $resourceId = null,
        ?array $changes = null,
        ?Request $request = null
    ): void {
        DB::table('activity_logs')->insert([
            'organization_id' => $orgId,
            'user_id' => $userId,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'changes' => $changes ? json_encode($changes) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Get activity logs for an organization.
     */
    public function getActivities(int $orgId, array $filters = []): array
    {
        $query = DB::table('activity_logs')
            ->where('organization_id', $orgId)
            ->orderByDesc('created_at');

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['resource_type'])) {
            $query->where('resource_type', $filters['resource_type']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        return $query->paginate($filters['per_page'] ?? 50)->toArray();
    }
}
