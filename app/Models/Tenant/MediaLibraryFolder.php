<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Media library folder stored in the tenant database.
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $parent_id
 * @property string|null $path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|MediaLibraryFolder search(?string $search)
 */
class MediaLibraryFolder extends Model
{
    use HasFactory;

    protected $table = 'media_library_folders';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'parent_id',
        'path',
    ];

    /**
     * Parent folder in the hierarchy.
     *
     * @return BelongsTo<MediaLibraryFolder, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child folders nested under this folder.
     *
     * @return HasMany<MediaLibraryFolder, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Media files stored in this folder.
     *
     * @return HasMany<Media, $this>
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class, 'folder_id');
    }

    /**
     * Scope a query to search by name.
     *
     * @param  Builder<MediaLibraryFolder>  $query
     * @param  string|null  $search
     * @return void
     */
    public function scopeSearch(Builder $query, ?string $search): void
    {
        $query->when($search, function (Builder $q, string $search) {
            $q->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
