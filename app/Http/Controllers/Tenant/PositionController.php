<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\StorePositionRequest;
use App\Http\Requests\Tenant\UpdatePositionRequest;
use App\Http\Resources\Tenant\PositionResource;
use App\Models\Tenant\Position;
use App\Services\Tenant\PositionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manages positions.
 */
class PositionController extends ApiController
{
    public function __construct(
        private readonly PositionService $positionService,
    ) {}

    /**
     * Get a paginated list of positions.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $filters = $request->validate([
            'search'        => ['nullable', 'string'],
            'department_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'in:active,inactive'],
        ]);

        $positions = $this->positionService->paginate(
            $filters,
            $request->integer('per_page', 15),
        );

        return $this->paginated($positions, PositionResource::collection($positions), 'Positions retrieved successfully.');
    }

    /**
     * Create a new position.
     *
     * @param  StorePositionRequest  $request
     * @return JsonResponse
     */
    public function store(StorePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);

        $position = $this->positionService->create($request->validated());

        return $this->created(
            new PositionResource($position),
            'Position created successfully.',
        );
    }

    /**
     * Get a single position.
     *
     * @param  Position  $position
     * @return JsonResponse
     */
    public function show(Position $position): JsonResponse
    {
        $this->authorize('view', $position);

        return $this->success(
            new PositionResource($this->positionService->find($position->id)),
            'Position retrieved successfully.',
        );
    }

    /**
     * Update an existing position.
     *
     * @param  UpdatePositionRequest  $request
     * @param  Position  $position
     * @return JsonResponse
     */
    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $this->authorize('update', $position);

        $position = $this->positionService->update($position, $request->validated());

        return $this->updated(
            new PositionResource($position),
            'Position updated successfully.',
        );
    }

    /**
     * Delete a position.
     *
     * @param  Position  $position
     * @return JsonResponse
     */
    public function destroy(Position $position): JsonResponse
    {
        $this->authorize('delete', $position);

        $this->positionService->delete($position);

        return $this->deleted('Position deleted successfully.');
    }
}
