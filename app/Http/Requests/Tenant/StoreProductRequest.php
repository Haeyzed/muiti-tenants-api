<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\Tenant\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validates product creation requests with product type support.
 */
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productType = $this->input('product_type', 'standard');

        $baseRules = [
            // Basic info
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'brand_id' => ['nullable', 'integer', Rule::exists('brands', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'mpn' => ['nullable', 'string', 'max:100'],
            'gtin' => ['nullable', 'string', 'max:100'],

            // Pricing
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],

            // Visibility & Status
            'is_visible' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'published_at' => ['nullable', 'date'],

            // Product Type
            'product_type' => ['required', new Enum(ProductType::class)],

            // SEO
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
            'canonical_url' => ['nullable', 'url', 'max:500'],

            // Tax
            'tax_class_id' => ['nullable', 'string'],

            // Tags
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')],

            // Media - Gallery (multiple images)
            'primary_image_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
            'gallery' => ['nullable', 'array'],
            'gallery.*.media_id' => ['required_with:gallery', 'integer', Rule::exists('media', 'id')],
            'gallery.*.alt_text' => ['nullable', 'string', 'max:255'],
            'gallery.*.caption' => ['nullable', 'string', 'max:500'],
            'gallery.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'gallery.*.is_primary' => ['nullable', 'boolean'],

            // YouTube Videos (multiple)
            'videos' => ['nullable', 'array'],
            'videos.*.video_url' => ['required_with:videos', 'string', 'max:500', 'regex:/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)[a-zA-Z0-9_-]{11}.*$/'],
            'videos.*.title' => ['nullable', 'string', 'max:255'],
            'videos.*.description' => ['nullable', 'string', 'max:1000'],
            'videos.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'videos.*.is_primary' => ['nullable', 'boolean'],

            // Product Relations
            'related_product_ids' => ['nullable', 'array'],
            'related_product_ids.*' => ['integer', Rule::exists('products', 'id')],
            'cross_sell_product_ids' => ['nullable', 'array'],
            'cross_sell_product_ids.*' => ['integer', Rule::exists('products', 'id')],
            'up_sell_product_ids' => ['nullable', 'array'],
            'up_sell_product_ids.*' => ['integer', Rule::exists('products', 'id')],

            // Attribute values
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*.attribute_id' => ['required_with:attribute_values', 'integer', Rule::exists('attributes', 'id')],
            'attribute_values.*.attribute_value_id' => ['required_with:attribute_values', 'integer', Rule::exists('attribute_values', 'id')],

            // Pricing tiers
            'pricing_tiers' => ['nullable', 'array'],
            'pricing_tiers.*.min_quantity' => ['required_with:pricing_tiers', 'integer', 'min:1'],
            'pricing_tiers.*.max_quantity' => ['nullable', 'integer', 'min:1'],
            'pricing_tiers.*.price' => ['required_with:pricing_tiers', 'numeric', 'min:0'],
            'pricing_tiers.*.customer_group_id' => ['nullable', 'string'],
        ];

        // Type-specific conditional rules
        $typeRules = match ($productType) {
            'standard' => [
                'weight' => ['required', 'numeric', 'min:0'],
                'length' => ['nullable', 'numeric', 'min:0'],
                'width' => ['nullable', 'numeric', 'min:0'],
                'height' => ['nullable', 'numeric', 'min:0'],
                'weight_unit' => ['nullable', 'string', 'in:kg,g,lb,oz'],
                'dimension_unit' => ['nullable', 'string', 'in:cm,m,mm,in,ft'],
                'inventory' => ['nullable', 'array'],
                'inventory.quantity' => ['nullable', 'integer', 'min:0'],
                'inventory.reserved_quantity' => ['nullable', 'integer', 'min:0'],
                'inventory.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            ],
            'digital' => [
                'download_limit' => ['nullable', 'integer', 'min:1'],
                'download_expiry_days' => ['nullable', 'integer', 'min:1'],
                'preview_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')],
                'digital_files' => ['required', 'array', 'min:1'],
                'digital_files.*.media_id' => ['required_with:digital_files', 'integer', Rule::exists('media', 'id')],
                'digital_files.*.file_name' => ['required_with:digital_files', 'string', 'max:255'],
                'digital_files.*.sort_order' => ['nullable', 'integer', 'min:0'],
            ],
            'service' => [
                'duration_minutes' => ['required', 'integer', 'min:1'],
                'buffer_minutes' => ['nullable', 'integer', 'min:0'],
                'max_participants' => ['nullable', 'integer', 'min:1'],
                'location_type' => ['required', 'string', 'in:physical,virtual,both'],
                'service_location' => ['nullable', 'string', 'max:500'],
                'provider_ids' => ['nullable', 'array'],
                'provider_ids.*' => ['integer', Rule::exists('users', 'id')],
            ],
            'combo' => [
                'combo_items' => ['required', 'array', 'min:2'],
                'combo_items.*.included_product_id' => ['required_with:combo_items', 'integer', Rule::exists('products', 'id')],
                'combo_items.*.included_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')],
                'combo_items.*.quantity' => ['required_with:combo_items', 'integer', 'min:1'],
                'combo_items.*.is_optional' => ['nullable', 'boolean'],
                'combo_items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'combo_items.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'allow_partial_combo' => ['sometimes', 'boolean'],
            ],
            default => [],
        };

        return array_merge($baseRules, $typeRules);
    }

    /**
     * Get custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'videos.*.video_url.regex' => 'The video URL must be a valid YouTube URL (e.g., https://www.youtube.com/watch?v=VIDEO_ID or https://youtu.be/VIDEO_ID).',
            'combo_items.min' => 'A combo product must include at least 2 items.',
            'digital_files.min' => 'A digital product must have at least 1 file attached.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Extract YouTube video ID from URL for storage
        if ($this->has('videos')) {
            $videos = $this->input('videos', []);
            foreach ($videos as $key => $video) {
                if (!empty($video['video_url'])) {
                    $videos[$key]['video_id'] = $this->extractYouTubeId($video['video_url']);
                }
            }
            $this->merge(['videos' => $videos]);
        }
    }

    /**
     * Extract YouTube video ID from various URL formats.
     */
    private function extractYouTubeId(string $url): ?string
    {
        $patterns = [
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]{11})/',
            '/youtube\.com\/shorts\/([a-zA-Z0-9_-]{11})/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }
}
