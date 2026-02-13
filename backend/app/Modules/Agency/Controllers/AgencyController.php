<?php

namespace App\Modules\Agency\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agency\Services\AgencyService;
use App\Modules\Agency\Services\TeamService;
use App\Modules\Agency\Services\WebhookService;
use App\Modules\Agency\Models\SubAccount;
use App\Modules\Agency\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgencyController extends Controller
{
    public function __construct(
        private AgencyService $agencyService,
        private TeamService $teamService,
        private WebhookService $webhookService
    ) {}

    // ---- Sub-Accounts ----

    public function listSubAccounts(Request $request): JsonResponse
    {
        $data = $this->agencyService->listSubAccounts($request->user()->organization_id);
        return response()->json(['data' => $data]);
    }

    public function createSubAccount(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'label' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $subAccount = $this->agencyService->createSubAccount(
            $request->user()->organization_id,
            $request->all()
        );

        return response()->json(['data' => $subAccount, 'message' => 'Sub-account created.'], 201);
    }

    public function subAccountMetrics(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $data = $this->agencyService->getAggregatedMetrics(
            $request->user()->organization_id,
            $request->get('date_from', now()->subDays(7)->format('Y-m-d')),
            $request->get('date_to', now()->format('Y-m-d'))
        );

        return response()->json(['data' => $data]);
    }

    // ---- White Label ----

    public function getWhiteLabel(Request $request): JsonResponse
    {
        $data = $this->agencyService->getWhiteLabel($request->user()->organization_id);
        return response()->json(['data' => $data]);
    }

    public function updateWhiteLabel(Request $request): JsonResponse
    {
        $request->validate([
            'brand_name' => 'nullable|string|max:255',
            'logo_url' => 'nullable|url',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'custom_domain' => 'nullable|string|max:255',
            'support_email' => 'nullable|email',
            'hide_powered_by' => 'nullable|boolean',
        ]);

        $data = $this->agencyService->updateWhiteLabel(
            $request->user()->organization_id,
            $request->all()
        );

        return response()->json(['data' => $data, 'message' => 'White-label settings updated.']);
    }

    // ---- Team ----

    public function listTeam(Request $request): JsonResponse
    {
        $data = $this->teamService->listMembers($request->user()->organization_id);
        return response()->json(['data' => $data]);
    }

    public function inviteMember(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:admin,manager,analyst,viewer',
        ]);

        $data = $this->teamService->invite(
            $request->user()->organization_id,
            $request->user()->id,
            $request->email,
            $request->role
        );

        return response()->json(['data' => $data, 'message' => 'Invitation sent.'], 201);
    }

    public function updateMemberRole(Request $request, int $userId): JsonResponse
    {
        $request->validate(['role' => 'required|in:admin,manager,analyst,viewer']);

        $success = $this->teamService->updateRole(
            $request->user()->organization_id,
            $userId,
            $request->role
        );

        return $success
            ? response()->json(['message' => 'Role updated.'])
            : response()->json(['message' => 'User not found.'], 404);
    }

    public function removeMember(Request $request, int $userId): JsonResponse
    {
        $success = $this->teamService->removeMember(
            $request->user()->organization_id,
            $userId
        );

        return $success
            ? response()->json(['message' => 'Member removed.'])
            : response()->json(['message' => 'Cannot remove this member.'], 400);
    }

    // ---- Webhooks ----

    public function listWebhooks(Request $request): JsonResponse
    {
        $webhooks = Webhook::where('organization_id', $request->user()->organization_id)->get();
        return response()->json(['data' => $webhooks]);
    }

    public function createWebhook(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url',
            'events' => 'required|array|min:1',
            'events.*' => 'string|in:' . implode(',', WebhookService::EVENTS),
        ]);

        $webhook = $this->webhookService->create(
            $request->user()->organization_id,
            $request->all()
        );

        return response()->json(['data' => $webhook, 'message' => 'Webhook created.'], 201);
    }

    public function deleteWebhook(Request $request, int $id): JsonResponse
    {
        Webhook::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id)
            ->delete();

        return response()->json(['message' => 'Webhook deleted.']);
    }

    public function webhookLogs(Request $request, int $id): JsonResponse
    {
        $webhook = Webhook::where('organization_id', $request->user()->organization_id)
            ->findOrFail($id);

        $logs = $webhook->logs()->orderByDesc('created_at')->paginate(25);

        return response()->json($logs);
    }
}
