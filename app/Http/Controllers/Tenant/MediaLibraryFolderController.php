<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\BulkDeleteMediaLibraryFoldersRequest;
use App\Http\Requests\Tenant\StoreMediaLibraryFolderRequest;
use App\Http\Requests\Tenant\UpdateMediaLibraryFolderRequest;
use App\Http\Resources\Tenant\MediaLibraryFolderResource;
use App\Models\Tenant\MediaLibraryFolder;
use App\Services\Tenant\MediaLibraryFolderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Media library folders for organizing tenant files.
 */
class MediaLibraryFolderController extends ApiController
{
    public function __construct(
        private readonly MediaLibraryFolderService $service,
    ) {}

    /**
     * List all media library folders.
     *
     * @param  Request  $request  Incoming HTTP request.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->query('search');
        $items = $this->service->getAll(is_string($search) ? $search : null);

        return $this->success(
            MediaLibraryFolderResource::collection($items),
            'Media folders retrieved successfully.',
        );
    }

    /**
     * Folder tree for the media library sidebar.
     */
    public function tree(): JsonResponse
    {
        return $this->success(
            ['tree' => $this->service->getTree()],
            'Media folder tree retrieved successfully.',
        );
    }

    /**
     * Create a media library folder.
     *
     * @param  StoreMediaLibraryFolderRequest  $request  Validated request payload.
     */
    public function store(StoreMediaLibraryFolderRequest $request): JsonResponse
    {
        $item = $this->service->create($request->validated());

        return $this->created(new MediaLibraryFolderResource($item), 'Media folder created successfully.');
    }

    /**
     * Find folder by route binding.
     *
     * @param  MediaLibraryFolder  $folder  Folder instance.
     */
    public function show(MediaLibraryFolder $folder): JsonResponse
    {
        $item = $this->service->findOrFail($folder->id);

        return $this->success(new MediaLibraryFolderResource($item), 'Media folder retrieved successfully.');
    }

    /**
     * Update media library folder.
     *
     * @param  UpdateMediaLibraryFolderRequest  $request  Validated request payload.
     * @param  MediaLibraryFolder  $folder  Folder instance.
     */
    public function update(UpdateMediaLibraryFolderRequest $request, MediaLibraryFolder $folder): JsonResponse
    {
        $item = $this->service->update($folder, $request->validated());

        return $this->updated(new MediaLibraryFolderResource($item), 'Media folder updated successfully.');
    }

    /**
     * Delete media library folder.
     *
     * @param  MediaLibraryFolder  $folder  Folder instance.
     */
    public function destroy(MediaLibraryFolder $folder): JsonResponse
    {
        $this->service->delete($folder);

        return $this->deleted('Media folder deleted successfully.');
    }

    /**
     * Delete multiple empty folders in one request.
     */
    public function bulkDestroy(BulkDeleteMediaLibraryFoldersRequest $request): JsonResponse
    {
        $deleted = $this->service->deleteMany($request->validated('ids'));

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} media folder(s) deleted successfully.",
        );
    }
}
