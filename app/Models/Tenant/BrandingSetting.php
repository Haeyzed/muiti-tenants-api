<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Concerns\Tenant\HasSingletonRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Branding and visual identity settings for a tenant store.
 */
class BrandingSetting extends Model implements HasMedia
{
    use HasSingletonRecord, InteractsWithMedia, LogsActivity;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'theme',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'theme' => 'array',
        ];
    }

    /**
     * Get the options for activity logging.
     *
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['theme'])
            ->logOnlyDirty();
    }

    /**
     * Register the media collections for branding.
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('store_logo')->singleFile();
        $this->addMediaCollection('store_banner')->singleFile();
        $this->addMediaCollection('favicon')->singleFile();
    }
}
