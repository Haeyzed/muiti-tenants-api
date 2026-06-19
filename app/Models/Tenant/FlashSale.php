<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Enums\Tenant\FlashSaleStatus;
use Database\Factories\Tenant\FlashSaleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Time-limited flash sale drop within a tenant store.
 */
class FlashSale extends Model
{
    /** @use HasFactory<FlashSaleFactory> */
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'starts_at',
        'ends_at',
        'status',
        'is_active',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return FlashSaleFactory
     */
    protected static function newFactory(): FlashSaleFactory
    {
        return FlashSaleFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => FlashSaleStatus::class,
            'is_active' => 'boolean',
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
            ->logOnly(['name', 'status', 'is_active', 'starts_at', 'ends_at'])
            ->logOnlyDirty();
    }

    /**
     * Get the options for generating the slug.
     *
     * @return SlugOptions
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }

    /**
     * Get the products attached to the flash sale.
     *
     * @return HasMany<FlashSaleProduct, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    /**
     * Get the rules for the flash sale.
     *
     * @return HasMany<FlashSaleRule, $this>
     */
    public function rules(): HasMany
    {
        return $this->hasMany(FlashSaleRule::class);
    }

    /**
     * Get the checkout queue for the flash sale.
     *
     * @return HasOne<CheckoutQueue, $this>
     */
    public function checkoutQueue(): HasOne
    {
        return $this->hasOne(CheckoutQueue::class);
    }

    /**
     * Determine if the flash sale is currently live.
     *
     * @return bool
     */
    public function isLive(): bool
    {
        return $this->status === FlashSaleStatus::Active
            && $this->is_active
            && now()->between($this->starts_at, $this->ends_at);
    }

    /**
     * Scope a query to search flash sales by name.
     *
     * @param  Builder<FlashSale>  $query
     * @param  string  $search
     * @return Builder<FlashSale>
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
