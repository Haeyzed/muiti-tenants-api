<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StoreTeamMemberRequest;
use App\Http\Requests\Tenant\UpdateTeamMemberRequest;
use App\Http\Resources\Tenant\TenantUserResource;
use App\Models\Tenant\TenantUser;
use App\Services\Tenant\TeamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Manages tenant team members.
 */
class TeamController extends ApiController
{
    public function __construct(
        private readonly TeamService $teamService,
    ) {}

    /**
     * Get a paginated list of team members.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', TenantUser::class);

        $filters = $request->validate([
            'search'    => ['nullable', 'string'],
            'is_active' => ['nullable', 'in:active,inactive'],
        ]);

        $members = $this->teamService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($members, TenantUserResource::collection($members), 'Team members retrieved successfully.');
    }

    /**
     * Create a new team member.
     *
     * @param StoreTeamMemberRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(StoreTeamMemberRequest $request): JsonResponse
    {
        $this->authorize('create', TenantUser::class);

        $member = $this->teamService->create($request->validated());

        return $this->created(
            new TenantUserResource($member),
            'Team member created successfully.',
        );
    }

    /**
     * Get a single team member.
     *
     * @param  TenantUser  $team
     * @return JsonResponse
     */
    public function show(TenantUser $team): JsonResponse
    {
        $this->authorize('view', $team);

        return $this->success(
            new TenantUserResource($this->teamService->find($team->id)),
            'Team member retrieved successfully.',
        );
    }

    /**
     * Update an existing team member.
     *
     * @param UpdateTeamMemberRequest $request
     * @param TenantUser $team
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(UpdateTeamMemberRequest $request, TenantUser $team): JsonResponse
    {
        $this->authorize('update', $team);

        $member = $this->teamService->update($team, $request->validated());

        return $this->updated(
            new TenantUserResource($member),
            'Team member updated successfully.',
        );
    }

    /**
     * Delete a team member.
     *
     * @param  TenantUser  $team
     * @return JsonResponse
     */
    public function destroy(TenantUser $team): JsonResponse
    {
        $this->authorize('delete', $team);

        $this->teamService->delete($team);

        return $this->deleted('Team member removed successfully.');
    }

    /**
     * Suspend a team member.
     *
     * @param  TenantUser  $team
     * @return JsonResponse
     */
    public function suspend(TenantUser $team): JsonResponse
    {
        $this->authorize('suspend', $team);

        $member = $this->teamService->suspend($team);

        return $this->success(
            new TenantUserResource($member),
            'Team member suspended successfully.',
        );
    }

    /**
     * Unsuspend a team member.
     *
     * @param  TenantUser  $team
     * @return JsonResponse
     */
    public function unsuspend(TenantUser $team): JsonResponse
    {
        $this->authorize('update', $team);

        $member = $this->teamService->unsuspend($team);

        return $this->success(
            new TenantUserResource($member),
            'Team member unsuspended successfully.',
        );
    }
}
