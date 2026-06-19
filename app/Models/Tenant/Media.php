<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\MediaLibrary\TenantMediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

/**
 * Media library file attached to a tenant model.
 *
 * @property int $id
 * @property int|null $folder_id
 * @property string $model_type
 * @property int $model_id
 * @property string|null $uuid
 * @property string $collection_name
 * @property string $name
 * @property string|null $title
 * @property string|null $alt_text
 * @property string|null $uploaded_by
 * @property string $file_name
 * @property string|null $mime_type
 * @property string $disk
 * @property string|null $conversions_disk
 * @property int $size
 * @property array<string, mixed> $manipulations
 * @property array<string, mixed> $custom_properties
 * @property array<string, mixed> $generated_conversions
 * @property array<string, mixed> $responsive_images
 * @property int|null $order_column
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Media search(?string $search)
 */
class Media extends SpatieMedia
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'folder_id',
        'model_type',
        'model_id',
        'uuid',
        'collection_name',
        'name',
        'title',
        'alt_text',
        'uploaded_by',
        'file_name',
        'mime_type',
        'disk',
        'conversions_disk',
        'size',
        'manipulations',
        'custom_properties',
        'generated_conversions',
        'responsive_images',
        'order_column',
    ];

    /**
     * Folder this media item is organized under.
     *
     * @return BelongsTo<MediaLibraryFolder, $this>
     */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaLibraryFolder::class, 'folder_id');
    }

    /**
     * Staff user who uploaded the file.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'uploaded_by');
    }

    /**
     * Scope a query to search by name, title, or file name.
     *
     * @param  Builder<Media>  $query
     * @param  string|null  $search
     * @return void
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        $query->when($search, function (Builder $q, string $search) {
            $q->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('file_name', 'like', "%{$search}%");
            });
        });
    }

    /**
     * Public URL for the original file.
     *
     * @return string
     */
    public function getUrlAttribute(): string
    {
        if (tenancy()->initialized) {
            return TenantMediaUrl::forPath($this->getPathRelativeToRoot());
        }

        return Storage::disk($this->disk)->url($this->getPathRelativeToRoot());
    }

    /**
     * Resolve URL via the tenant asset route (used by Spatie helpers/resources).
     *
     * @param  string  $conversionName
     * @return string
     */
    public function getUrl(string $conversionName = ''): string
    {
        if (tenancy()->initialized) {
            return TenantMediaUrl::forPath($this->getPathRelativeToRoot($conversionName));
        }

        return parent::getUrl($conversionName);
    }

    /**
     * Resolve the storage path for library files (folder-based layout).
     *
     * @param  string  $conversionName
     * @return string
     */
    public function getPathRelativeToRoot(string $conversionName = ''): string
    {
        if ($this->collection_name === 'library') {
            $folderSegment = $this->folder_id ?? 'root';

            return "media/library/{$folderSegment}/{$this->file_name}";
        }

        return parent::getPathRelativeToRoot($conversionName);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'manipulations' => 'array',
            'custom_properties' => 'array',
            'generated_conversions' => 'array',
            'responsive_images' => 'array',
            'size' => 'integer',
            'order_column' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
