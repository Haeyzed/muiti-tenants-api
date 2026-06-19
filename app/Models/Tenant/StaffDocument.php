<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Document uploaded for a staff member.
 */
class StaffDocument extends Model implements HasMedia
{
    use InteractsWithMedia;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'staff_id',
        'title',
        'document_type',
        'expires_at',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'date',
        ];
    }

    /**
     * Get the staff member this document belongs to.
     *
     * @return BelongsTo<Staff, $this>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Get the user who uploaded the document.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'uploaded_by');
    }

    /**
     * Register the media collections for the model.
     *
     * @return void
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('staff_documents')->singleFile();
    }
}
