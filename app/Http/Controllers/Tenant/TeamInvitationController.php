<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\AcceptTeamInvitationRequest;
use App\Http\Requests\Tenant\SendTeamInvitationRequest;
use App\Http\Resources\Tenant\TeamInvitationResource;
use App\Http\Resources\Tenant\TenantUserResource;
use App\Models\Tenant\TeamInvitation;
use App\Models\Tenant\TenantUser;
use App\Services\Tenant\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

/**
 * Manages team member invitations.
 */
class TeamInvitationController extends ApiController
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    /**
     * Get a paginated list of team invitations.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TeamInvitation::class);

        $filters = $request->validate([
            'email'  => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
        ]);

        $invitations = $this->invitationService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($invitations, TeamInvitationResource::collection($invitations), 'Invitations retrieved successfully.');
    }

    /**
     * Send a new team invitation.
     *
     * @param SendTeamInvitationRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(SendTeamInvitationRequest $request): JsonResponse
    {
        $this->authorize('create', TeamInvitation::class);

        /** @var TenantUser $inviter */
        $inviter = $request->user();

        $invitation = $this->invitationService->send($inviter, $request->validated());

        return $this->created(
            new TeamInvitationResource($invitation),
            'Invitation sent successfully.',
        );
    }

    /**
     * Get a single team invitation.
     *
     * @param  TeamInvitation  $invitation
     * @return JsonResponse
     */
    public function show(TeamInvitation $invitation): JsonResponse
    {
        $this->authorize('view', $invitation);

        return $this->success(
            new TeamInvitationResource($this->invitationService->find($invitation->id)),
            'Invitation retrieved successfully.',
        );
    }

    /**
     * Resend a team invitation.
     *
     * @param TeamInvitation $invitation
     * @return JsonResponse
     * @throws Throwable
     */
    public function resend(TeamInvitation $invitation): JsonResponse
    {
        $this->authorize('update', $invitation);

        try {
            $invitation = $this->invitationService->resend($invitation);
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        return $this->success(
            new TeamInvitationResource($invitation),
            'Invitation resent successfully.',
        );
    }

    /**
     * Cancel a team invitation.
     *
     * @param  TeamInvitation  $invitation
     * @return JsonResponse
     */
    public function destroy(TeamInvitation $invitation): JsonResponse
    {
        $this->authorize('delete', $invitation);

        $invitation = $this->invitationService->cancel($invitation);

        return $this->success(
            new TeamInvitationResource($invitation),
            'Invitation cancelled successfully.',
        );
    }

    /**
     * Accept a team invitation.
     *
     * @param AcceptTeamInvitationRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function accept(AcceptTeamInvitationRequest $request): JsonResponse
    {
        try {
            $invitation = $this->invitationService->findByToken($request->validated('token'));
            $user = $this->invitationService->accept($invitation, $request->safe()->only(['name', 'password']));
        } catch (RuntimeException $exception) {
            return $this->validationError(null, $exception->getMessage());
        }

        $token = $user->createToken('tenant-api')->plainTextToken;

        return $this->created([
            'user' => new TenantUserResource($user),
            'token' => $token,
        ], 'Invitation accepted successfully.');
    }
}
