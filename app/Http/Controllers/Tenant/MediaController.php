<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Tenant\BulkDeleteMediaRequest;
use App\Http\Requests\Tenant\BulkUpdateMediaRequest;
use App\Http\Requests\Tenant\BulkUploadMediaRequest;
use App\Http\Requests\Tenant\CopyMediaRequest;
use App\Http\Requests\Tenant\MoveMediaRequest;
use App\Http\Requests\Tenant\UpdateMediaRequest;
use App\Http\Requests\Tenant\UploadMediaRequest;
use App\Http\Resources\Tenant\MediaResource;
use App\Models\Tenant\Media;
use App\Services\Tenant\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Media library files for the tenant store.
 */
class MediaController extends ApiController
{
    public function __construct(
        private readonly MediaService $service,
    ) {}

    /**
     * Get paginated media library items.
     *
     * @param  Request  $request  Incoming HTTP request.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 24);
        $search = $request->query('search');
        $folderId = $request->filled('folder_id') ? $request->integer('folder_id') : null;
        $mimeType = $request->query('mime_type');
        $rootOnly = $request->boolean('root_only');

        $items = $this->service->paginate(
            $perPage,
            is_string($search) ? $search : null,
            $folderId,
            is_string($mimeType) ? $mimeType : null,
            $rootOnly,
        );

        return $this->paginated($items, MediaResource::collection($items), 'Media retrieved successfully.');
    }

    /**
     * KPI card metrics for the media library.
     */
    public function metrics(): JsonResponse
    {
        return $this->success(
            ['cards' => $this->service->getMetrics()],
            'Media KPI metrics retrieved successfully.',
        );
    }

    /**
     * Upload a file to the media library.
     *
     * @param  UploadMediaRequest  $request  Validated upload payload.
     */
    public function store(UploadMediaRequest $request): JsonResponse
    {
        $item = $this->service->upload(
            $request->file('file'),
            $request->safe()->except(['file']),
        );

        return $this->created(new MediaResource($item), 'Media uploaded successfully.');
    }

    /**
     * Upload multiple files to the media library.
     */
    public function bulkUpload(BulkUploadMediaRequest $request): JsonResponse
    {
        /** @var list<\Illuminate\Http\UploadedFile> $files */
        $files = $request->file('files', []);
        $items = $this->service->uploadMany($files, $request->safe()->except(['files']));

        return $this->created(
            [
                'uploaded' => count($items),
                'items' => MediaResource::collection(collect($items)),
            ],
            count($items).' media file(s) uploaded successfully.',
        );
    }

    /**
     * Move multiple media items into a folder.
     */
    public function move(MoveMediaRequest $request): JsonResponse
    {
        try {
            $items = $this->service->moveMany(
                $request->validated('ids'),
                $request->validated('folder_id'),
            );
        } catch (RuntimeException $exception) {
            return $this->badRequest($exception->getMessage(), ['moved' => 0, 'items' => []]);
        }

        return $this->success(
            [
                'moved' => count($items),
                'items' => MediaResource::collection(collect($items)),
            ],
            count($items).' media file(s) moved successfully.',
        );
    }

    /**
     * Copy multiple media items into a folder.
     */
    public function copy(CopyMediaRequest $request): JsonResponse
    {
        try {
            $items = $this->service->copyMany(
                $request->validated('ids'),
                $request->validated('folder_id'),
            );
        } catch (RuntimeException $exception) {
            return $this->badRequest($exception->getMessage(), ['copied' => 0, 'items' => []]);
        }

        return $this->created(
            [
                'copied' => count($items),
                'items' => MediaResource::collection(collect($items)),
            ],
            count($items).' media file(s) copied successfully.',
        );
    }

    /**
     * Update metadata for multiple media items.
     */
    public function bulkUpdate(BulkUpdateMediaRequest $request): JsonResponse
    {
        $items = $this->service->updateMany(
            $request->validated('ids'),
            $request->safe()->except(['ids']),
        );

        return $this->success(
            [
                'updated' => count($items),
                'items' => MediaResource::collection(collect($items)),
            ],
            count($items).' media file(s) updated successfully.',
        );
    }

    /**
     * Find media by route binding.
     *
     * @param  Media  $media  Media instance.
     */
    public function show(Media $media): JsonResponse
    {
        $item = $this->service->findOrFail($media->id);

        return $this->success(new MediaResource($item), 'Media retrieved successfully.');
    }

    /**
     * Update media metadata.
     *
     * @param  UpdateMediaRequest  $request  Validated request payload.
     * @param  Media  $media  Media instance.
     */
    public function update(UpdateMediaRequest $request, Media $media): JsonResponse
    {
        try {
            $item = $this->service->update($media, $request->validated());
        } catch (RuntimeException $exception) {
            return $this->badRequest($exception->getMessage());
        }

        return $this->updated(new MediaResource($item), 'Media updated successfully.');
    }

    /**
     * Delete media.
     *
     * @param  Media  $media  Media instance.
     */
    public function destroy(Media $media): JsonResponse
    {
        try {
            $this->service->delete($media);
        } catch (RuntimeException $exception) {
            return $this->badRequest($exception->getMessage());
        }

        return $this->deleted('Media deleted successfully.');
    }

    /**
     * Delete multiple media items in one request.
     */
    public function bulkDestroy(BulkDeleteMediaRequest $request): JsonResponse
    {
        $deleted = $this->service->deleteMany($request->validated('ids'));

        return $this->success(
            ['deleted' => $deleted],
            "{$deleted} media file(s) deleted successfully.",
        );
    }
}
