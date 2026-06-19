<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\MediaLibraryFolder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Tenant media library folder records and queries.
 */
class MediaLibraryFolderService
{
    /**
     * Get all folders as a flat list.
     *
     * @param  string|null  $search
     * @return Collection<int, MediaLibraryFolder>
     */
    public function getAll(?string $search = null): Collection
    {
        return MediaLibraryFolder::query()
            ->search($search)
            ->withCount('media')
            ->orderBy('path')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get folder tree for the media library sidebar.
     *
     * @return list<array<string, mixed>>
     */
    public function getTree(): array
    {
        $folders = MediaLibraryFolder::query()
            ->withCount('media')
            ->orderBy('name')
            ->get();

        return $this->buildTree($folders);
    }

    /**
     * Find folder by ID or fail.
     *
     * @param  int  $id
     * @return MediaLibraryFolder
     */
    public function findOrFail(int $id): MediaLibraryFolder
    {
        return MediaLibraryFolder::query()
            ->withCount('media')
            ->findOrFail($id);
    }

    /**
     * Create a folder.
     *
     * @param  array<string, mixed>  $data
     * @return MediaLibraryFolder
     */
    public function create(array $data): MediaLibraryFolder
    {
        $data['path'] = $this->buildPath($data['name'], $data['parent_id'] ?? null);

        return MediaLibraryFolder::query()->create($data);
    }

    /**
     * Update folder.
     *
     * @param  MediaLibraryFolder  $folder
     * @param  array<string, mixed>  $data
     * @return MediaLibraryFolder
     */
    public function update(MediaLibraryFolder $folder, array $data): MediaLibraryFolder
    {
        if (isset($data['name']) || array_key_exists('parent_id', $data)) {
            $data['path'] = $this->buildPath(
                $data['name'] ?? $folder->name,
                $data['parent_id'] ?? $folder->parent_id,
            );
        }

        $folder->update($data);

        return $folder->fresh()->loadCount('media');
    }

    /**
     * Delete folder when empty.
     *
     * @param  MediaLibraryFolder  $folder
     * @return bool
     */
    public function delete(MediaLibraryFolder $folder): bool
    {
        if ($folder->media()->exists() || $folder->children()->exists()) {
            return false;
        }

        return $folder->delete();
    }

    /**
     * Delete multiple empty folders by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function deleteMany(array $ids): int
    {
        $deleted = 0;

        MediaLibraryFolder::query()
            ->whereIn('id', $ids)
            ->get()
            ->each(function (MediaLibraryFolder $folder) use (&$deleted): void {
                if ($this->delete($folder)) {
                    $deleted++;
                }
            });

        return $deleted;
    }

    /**
     * Force delete a folder permanently.
     *
     * @param  MediaLibraryFolder  $folder
     * @return bool
     */
    public function forceDelete(MediaLibraryFolder $folder): bool
    {
        return $folder->forceDelete();
    }

    /**
     * Force delete multiple folders by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function forceDeleteMany(array $ids): int
    {
        return MediaLibraryFolder::query()->whereIn('id', $ids)->forceDelete();
    }

    /**
     * Restore a soft-deleted folder.
     *
     * @param  MediaLibraryFolder  $folder
     * @return MediaLibraryFolder
     */
    public function restore(MediaLibraryFolder $folder): MediaLibraryFolder
    {
        $folder->restore();

        return $folder->fresh()->loadCount('media');
    }

    /**
     * Restore multiple soft-deleted folders by ID.
     *
     * @param  list<int>  $ids
     * @return int
     */
    public function restoreMany(array $ids): int
    {
        return MediaLibraryFolder::query()->onlyTrashed()->whereIn('id', $ids)->restore();
    }

    /**
     * Build a nested tree structure of folders.
     *
     * @param  Collection<int, MediaLibraryFolder>  $folders
     * @param  int|null  $parentId
     * @return list<array<string, mixed>>
     */
    private function buildTree(Collection $folders, ?int $parentId = null): array
    {
        return $folders
            ->where('parent_id', $parentId)
            ->values()
            ->map(fn (MediaLibraryFolder $folder): array => [
                'id' => $folder->id,
                'name' => $folder->name,
                'path' => $folder->path,
                'parent_id' => $folder->parent_id,
                'media_count' => $folder->media_count ?? $folder->media()->count(),
                'children' => $this->buildTree($folders, $folder->id),
            ])
            ->all();
    }

    /**
     * Build the path for a folder based on its parent.
     *
     * @param  string  $name
     * @param  int|null  $parentId
     * @return string
     */
    private function buildPath(string $name, ?int $parentId): string
    {
        if ($parentId === null) {
            return $name;
        }

        $parent = MediaLibraryFolder::query()->find($parentId);

        return $parent ? trim($parent->path.'/'.$name, '/') : $name;
    }
}
