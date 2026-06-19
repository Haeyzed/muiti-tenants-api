<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Database\Factories\Tenant\CustomerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Customer profile within a tenant store.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $customer_group_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $gender
 * @property int $loyalty_points
 * @property string $total_spent
 * @property int $orders_count
 * @property bool $is_active
 * @property string|null $notes_summary
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read TenantUser|null $user
 * @property-read CustomerGroup|null $group
 * @property-read Collection<int, CustomerAddress> $addresses
 * @property-read Collection<int, CustomerTag> $tags
 * @property-read Collection<int, CustomerNote> $notes
 * @property-read Collection<int, Order> $orders
 * @property-read Collection<int, Cart> $carts
 * @method static Builder<Customer>|Customer query()
 * @method static Builder<Customer>|Customer filter(array $filters)
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, LogsActivity, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'customer_group_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'loyalty_points',
        'total_spent',
        'orders_count',
        'is_active',
        'notes_summary',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return CustomerFactory
     */
    protected static function newFactory(): CustomerFactory
    {
        return CustomerFactory::new();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'loyalty_points' => 'integer',
            'total_spent' => 'decimal:2',
            'orders_count' => 'integer',
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
            ->logOnly(['first_name', 'last_name', 'email', 'phone', 'customer_group_id', 'is_active'])
            ->logOnlyDirty();
    }

    /**
     * Get the user associated with the customer.
     *
     * @return BelongsTo<TenantUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'user_id');
    }

    /**
     * Get the customer group this customer belongs to.
     *
     * @return BelongsTo<CustomerGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    /**
     * Get the addresses for the customer.
     *
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    /**
     * Get the tags associated with the customer.
     *
     * @return BelongsToMany<CustomerTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CustomerTag::class);
    }

    /**
     * Get the notes for the customer.
     *
     * @return HasMany<CustomerNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class);
    }

    /**
     * Get the orders placed by the customer.
     *
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the carts belonging to the customer.
     *
     * @return HasMany<Cart, $this>
     */
    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the full name of the customer.
     *
     * @return string
     */
    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * Scope a query to filter customers.
     *
     * @param  Builder<Customer>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Customer>
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['search']), function (Builder $q) use ($filters): void {
                $search = (string) $filters['search'];
                $q->where(function (Builder $builder) use ($search): void {
                    $builder->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when(!empty($filters['customer_group_id']), function (Builder $q) use ($filters): void {
                $groupIds = is_array($filters['customer_group_id'])
                    ? $filters['customer_group_id']
                    : explode(',', (string) $filters['customer_group_id']);

                $q->whereIn('customer_group_id', $groupIds);
            })
            ->when(!empty($filters['is_active']), function (Builder $q) use ($filters): void {
                $statuses = is_array($filters['is_active'])
                    ? $filters['is_active']
                    : explode(',', (string) $filters['is_active']);

                $booleans = [];
                if (in_array('active', $statuses, true)) {
                    $booleans[] = true;
                }
                if (in_array('inactive', $statuses, true)) {
                    $booleans[] = false;
                }

                if (!empty($booleans)) {
                    $q->whereIn('is_active', $booleans);
                }
            });
    }
}
