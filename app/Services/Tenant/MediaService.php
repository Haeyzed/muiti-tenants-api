<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Media;
use App\Models\Tenant\MediaLibraryFolder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Tenant media library files and queries.
 */
class MediaService
{

    private const string LIBRARY_COLLECTION = 'library';

    private const string LIBRARY_MODEL_TYPE = MediaLibraryFolder::class;

    /**
     * Base query for library media items.
     *
     * @return Builder<Media>
     */
    private function query(): Builder
    {
        return Media::query()
            ->where('collection_name', self::LIBRARY_COLLECTION)
            ->with(['folder', 'uploader']);
    }

    /**
     * Get paginated media library items.
     *
     * @param  int  $perPage
     * @param  string|null  $search
     * @param  int|null  $folderId
     * @param  string|null  $mimeType
     * @param  bool  $rootOnly
     * @return LengthAwarePaginator<int, Media>
     */
    public function paginate(
        int $perPage = 24,
        ?string $search = null,
        ?int $folderId = null,
        ?string $mimeType = null,
        bool $rootOnly = false,
    ): LengthAwarePaginator {
        return $this->query()
            ->search($search)
            ->when($folderId !== null, fn (Builder $q) => $q->where('folder_id', $folderId))
            ->when($folderId === null && $rootOnly, fn (Builder $q) => $q->whereNull('folder_id'))
            ->when($mimeType, fn (Builder $q) => $q->where('mime_type', 'like', "{$mimeType}%"))
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Find media by ID or fail.
     *
     * @param  int  $id
     * @return Media
     */
    public function findOrFail(int $id): Media
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * Upload a file into the media library.
     *
     * @param  UploadedFile  $file
     * @param  array<string, mixed>  $meta
     * @return Media
     */
    public function upload(UploadedFile $file, array $meta = []): Media
    {
        $folderId = isset($meta['folder_id']) ? (int) $meta['folder_id'] : null;
        $disk = (string) config('media-library.disk_name', 'public');
        $storedName = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        Storage::disk($disk)->putFileAs(
            $this->libraryDirectory($folderId),
            $file,
            $storedName,
        );

        return Media::query()->create([
            'folder_id' => $folderId,
            'model_type' => self::LIBRARY_MODEL_TYPE,
            'model_id' => $folderId ?? 0,
            'uuid' => (string) Str::uuid(),
            'collection_name' => self::LIBRARY_COLLECTION,
            'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'title' => $meta['title'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'alt_text' => $meta['alt_text'] ?? null,
            'uploaded_by' => Auth::id(),
            'file_name' => $storedName,
            'mime_type' => $file->getClientMimeType(),
            'disk' => $disk,
            'conversions_disk' => config('media-library.conversions_disk_name'),
            'size' => $file->getSize(),
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);
    }

    /**
     * Upload multiple files into the media library.
     *
     * @param  list<UploadedFile>  $files
     * @param  array<string, mixed>  $meta
     * @return list<Media>
     */
    public function uploadMany(array $files, array $meta = []): array
    {
        $uploaded = [];

        foreach ($files as $file) {
            $uploaded[] = $this->upload($file, $meta);
        }

        return $uploaded;
    }

    /**
     * Update media metadata and relocate the file when the folder changes.
     *
     * @param Media $media
     * @param array<string, mixed> $data
     * @return Media
     * @throws Throwable
     */
    public function update(Media $media, array $data): Media
    {
        $this->assertLibraryMedia($media);

        return DB::transaction(function () use ($media, $data): Media {
            if (array_key_exists('folder_id', $data)) {
                $folderId = $data['folder_id'] !== null ? (int) $data['folder_id'] : null;
                $this->relocateLibraryFile($media, $folderId);
                $media->folder_id = $folderId;
                $media->model_id = $folderId ?? 0;
                unset($data['folder_id']);
            }

            if ($data !== []) {
                $media->fill($data);
            }

            $media->save();

            return $media->fresh(['folder', 'uploader']);
        });
    }

    /**
     * Move multiple library files into a folder.
     *
     * @param  list<int>  $ids
     * @param  int|null  $folderId
     * @return list<Media>
     */
    public function moveMany(array $ids, ?int $folderId): array
    {
        $moved = [];

        $this->libraryMediaByIds($ids)->each(function (Media $media) use ($folderId, &$moved): void {
            $moved[] = $this->update($media, ['folder_id' => $folderId]);
        });

        return $moved;
    }

    /**
     * Copy multiple library files into a folder.
     *
     * @param  list<int>  $ids
     * @param  int|null  $folderId
     * @return list<Media>
     */
    public function copyMany(array $ids, ?int $folderId): array
    {
        $copied = [];

        $this->libraryMediaByIds($ids)->each(function (Media $media) use ($folderId, &$copied): void {
            $copied[] = $this->copy($media, $folderId);
        });

        return $copied;
    }

    /**
     * Apply shared metadata updates to multiple library files.
     *
     * @param  list<int>  $ids
     * @param  array<string, mixed>  $data
     * @return list<Media>
     */
    public function updateMany(array $ids, array $data): array
    {
        $payload = array_intersect_key($data, array_flip(['title', 'alt_text']));

        if ($payload === []) {
            return [];
        }

        $updated = [];

        $this->libraryMediaByIds($ids)->each(function (Media $media) use ($payload, &$updated): void {
            $media->update($payload);
            $updated[] = $media->fresh(['folder', 'uploader']);
        });

        return $updated;
    }

    /**
     * Delete media and remove the underlying file from storage.
     *
     * @param  Media  $media
     * @return bool
     */
    public function delete(Media $media): bool
    {
        $this->assertLibraryMedia($media);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();

        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }

        return $media->delete();
    }

    /**
     * Delete multiple media items by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        $deleted = 0;

        $this->libraryMediaByIds($ids)->each(function (Media $media) use (&$deleted): void {
            if ($this->delete($media)) {
                $deleted++;
            }
        });

        return $deleted;
    }

    /**
     * Force delete a media item permanently.
     *
     * @param  Media  $media
     * @return bool
     */
    public function forceDelete(Media $media): bool
    {
        $this->assertLibraryMedia($media);

        $disk = Storage::disk($media->disk);
        $path = $media->getPathRelativeToRoot();

        if ($path && $disk->exists($path)) {
            $disk->delete($path);
        }

        return $media->forceDelete();
    }

    /**
     * Force delete multiple media items by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function forceDeleteMany(array $ids): int
    {
        $deleted = 0;

        $this->libraryMediaByIds($ids)->each(function (Media $media) use (&$deleted): void {
            if ($this->forceDelete($media)) {
                $deleted++;
            }
        });

        return $deleted;
    }

    /**
     * Restore a soft-deleted media item.
     *
     * @param  Media  $media
     * @return Media
     */
    public function restore(Media $media): Media
    {
        $media->restore();

        return $media->fresh(['folder', 'uploader']);
    }

    /**
     * Restore multiple soft-deleted media items by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return Media::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * KPI card metrics for the media library.
     *
     * @return list<array{key: string, label: string, value: int|string|float}>
     */
    public function getMetrics(): array
    {
        $total = Media::query()->where('collection_name', self::LIBRARY_COLLECTION)->count();
        $images = Media::query()
            ->where('collection_name', self::LIBRARY_COLLECTION)
            ->where('mime_type', 'like', 'image/%')
            ->count();
        $totalSize = (int) Media::query()
            ->where('collection_name', self::LIBRARY_COLLECTION)
            ->sum('size');

        return [
            ['key' => 'total', 'label' => 'Total Files', 'value' => $total],
            ['key' => 'images', 'label' => 'Images', 'value' => $images],
            ['key' => 'total_size_mb', 'label' => 'Storage (MB)', 'value' => round($totalSize / 1024 / 1024, 2)],
        ];
    }

    /**
     * Copy a single media file to a new folder.
     *
     * @param  Media  $media
     * @param  int|null  $folderId
     * @return Media
     */
    private function copy(Media $media, ?int $folderId): Media
    {
        $disk = Storage::disk($media->disk);
        $sourcePath = $media->getPathRelativeToRoot();

        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException("Source file not found for media [{$media->id}].");
        }

        $extension = pathinfo($media->file_name, PATHINFO_EXTENSION);
        $storedName = Str::uuid()->toString().($extension !== '' ? ".{$extension}" : '');
        $destinationPath = $this->libraryPath($folderId, $storedName);

        $disk->makeDirectory($this->libraryDirectory($folderId));
        $disk->copy($sourcePath, $destinationPath);

        $copy = Media::query()->create([
            'folder_id' => $folderId,
            'model_type' => self::LIBRARY_MODEL_TYPE,
            'model_id' => $folderId ?? 0,
            'uuid' => (string) Str::uuid(),
            'collection_name' => self::LIBRARY_COLLECTION,
            'name' => $media->name,
            'title' => $media->title,
            'alt_text' => $media->alt_text,
            'uploaded_by' => Auth::id(),
            'file_name' => $storedName,
            'mime_type' => $media->mime_type,
            'disk' => $media->disk,
            'conversions_disk' => $media->conversions_disk,
            'size' => $media->size,
            'manipulations' => $media->manipulations,
            'custom_properties' => $media->custom_properties,
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        return $copy->fresh(['folder', 'uploader']);
    }

    /**
     * Relocate a library file to a new folder on the disk.
     *
     * @param  Media  $media
     * @param  int|null  $folderId
     * @return void
     */
    private function relocateLibraryFile(Media $media, ?int $folderId): void
    {
        if ((int) ($media->folder_id ?? 0) === (int) ($folderId ?? 0)) {
            return;
        }

        $disk = Storage::disk($media->disk);
        $currentPath = $media->getPathRelativeToRoot();
        $newPath = $this->libraryPath($folderId, $media->file_name);

        if ($currentPath === $newPath) {
            return;
        }

        if ($disk->exists($currentPath)) {
            $disk->makeDirectory($this->libraryDirectory($folderId));
            $disk->move($currentPath, $newPath);
        }
    }

    /**
     * Get the directory path for a library folder.
     *
     * @param  int|null  $folderId
     * @return string
     */
    private function libraryDirectory(?int $folderId): string
    {
        return 'media/library/'.($folderId ?? 'root');
    }

    /**
     * Get the full path for a file within a library folder.
     *
     * @param  int|null  $folderId
     * @param  string  $fileName
     * @return string
     */
    private function libraryPath(?int $folderId, string $fileName): string
    {
        return $this->libraryDirectory($folderId).'/'.$fileName;
    }

    /**
     * Retrieve a collection of media items by their IDs.
     *
     * @param  list<int>  $ids
     * @return Collection<int, Media>
     */
    private function libraryMediaByIds(array $ids): Collection
    {
        $items = $this->query()->whereIn('id', $ids)->get();

        if ($items->count() !== count(array_unique($ids))) {
            throw new RuntimeException('One or more media items could not be found in the library.');
        }

        return $items;
    }

    /**
     * Ensure the given media belongs to the library collection.
     *
     * @param  Media  $media
     * @return void
     */
    private function assertLibraryMedia(Media $media): void
    {
        if ($media->collection_name !== self::LIBRARY_COLLECTION) {
            throw new RuntimeException('Only library media items can be managed through the media library.');
        }
    }
}
