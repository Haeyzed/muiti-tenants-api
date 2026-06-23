<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

/**
 * Product type enumeration for catalog classification.
 */
enum ProductType: string
{
    case Standard = 'standard';
    case Digital = 'digital';
    case Service = 'service';
    case Combo = 'combo';

    /**
     * Get human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Standard => 'Standard Product',
            self::Digital => 'Digital Product',
            self::Service => 'Service',
            self::Combo => 'Combo / Bundle',
        };
    }

    /**
     * Get description of the type.
     */
    public function description(): string
    {
        return match ($this) {
            self::Standard => 'Physical product with inventory tracking and shipping.',
            self::Digital => 'Downloadable product with file attachments and no shipping.',
            self::Service => 'Non-physical service with booking duration and provider assignment.',
            self::Combo => 'Bundle of multiple products sold together at a combined price.',
        };
    }

    /**
     * Get fields applicable to this product type.
     *
     * @return list<string>
     */
    public function applicableFields(): array
    {
        return match ($this) {
            self::Standard => [
                'sku', 'barcode', 'mpn', 'gtin', 'weight', 'length', 'width', 'height',
                'weight_unit', 'dimension_unit', 'cost_price', 'compare_at_price',
                'inventory', 'primary_image_media_id', 'gallery_media_ids',
            ],
            self::Digital => [
                'sku', 'cost_price', 'compare_at_price',
                'download_limit', 'download_expiry_days', 'file_media_ids',
                'preview_media_id', 'primary_image_media_id', 'gallery_media_ids',
            ],
            self::Service => [
                'sku', 'cost_price', 'compare_at_price',
                'duration_minutes', 'buffer_minutes', 'max_participants',
                'location_type', 'service_location', 'provider_ids',
                'primary_image_media_id', 'gallery_media_ids',
            ],
            self::Combo => [
                'sku', 'barcode', 'cost_price', 'compare_at_price',
                'combo_items', 'allow_partial_combo', 'primary_image_media_id', 'gallery_media_ids',
            ],
        };
    }

    /**
     * Get fields that are required for this type.
     *
     * @return list<string>
     */
    public function requiredFields(): array
    {
        return match ($this) {
            self::Standard => ['sku', 'price', 'weight'],
            self::Digital => ['sku', 'price', 'file_media_ids'],
            self::Service => ['sku', 'price', 'duration_minutes'],
            self::Combo => ['sku', 'price', 'combo_items'],
        };
    }

    /**
     * Check if shipping is applicable.
     */
    public function requiresShipping(): bool
    {
        return match ($this) {
            self::Standard, self::Combo => true,
            self::Digital, self::Service => false,
        };
    }

    /**
     * Check if inventory tracking is applicable.
     */
    public function tracksInventory(): bool
    {
        return match ($this) {
            self::Standard, self::Combo => true,
            self::Digital, self::Service => false,
        };
    }

    /**
     * Get all values as array.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public static function toArray(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
            ],
            self::cases()
        );
    }
}
