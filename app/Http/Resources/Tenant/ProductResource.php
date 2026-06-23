<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Enums\Tenant\ProductType;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $productType = ProductType::tryFrom($this->product_type);

        return [
            // Core info
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'mpn' => $this->mpn,
            'gtin' => $this->gtin,

            // Product Type
            'product_type' => [
                'value' => $this->product_type,
                'label' => $productType?->label(),
                'description' => $productType?->description(),
                'requires_shipping' => $productType?->requiresShipping() ?? true,
                'tracks_inventory' => $productType?->tracksInventory() ?? true,
            ],

            // Pricing
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price,
            'cost_price' => $this->cost_price,
            'discount_percentage' => $this->discountPercentage(),
            'is_on_sale' => $this->isOnSale(),
            'profit_margin' => $this->profitMargin(),

            // Status
            'is_visible' => $this->is_visible,
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->toIso8601String(),

            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'canonical_url' => $this->canonical_url,

            // Dimensions
            'weight' => $this->weight,
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'weight_unit' => $this->weight_unit,
            'dimension_unit' => $this->dimension_unit,

            // Media - Primary Image
            'primary_image_media' => $this->whenLoaded('primaryImageMedia', fn (): array => [
                'id' => $this->primaryImageMedia?->id,
                'file_name' => $this->primaryImageMedia?->file_name,
                'mime_type' => $this->primaryImageMedia?->mime_type,
                'url' => $this->primaryImageMedia?->getUrl(),
            ]),

            // Gallery Images (multiple, ordered)
            'gallery' => $this->whenLoaded('productImages', function () {
                return $this->productImages->map(fn ($pi) => [
                    'id' => $pi->id,
                    'sort_order' => $pi->sort_order,
                    'alt_text' => $pi->alt_text,
                    'caption' => $pi->caption,
                    'is_primary' => $pi->is_primary_gallery,
                    'media' => [
                        'id' => $pi->media->id,
                        'file_name' => $pi->media->file_name,
                        'url' => $pi->media->getUrl(),
                    ],
                ]);
            }),

            // YouTube Videos (multiple)
            'videos' => $this->whenLoaded('videos', function () {
                return $this->videos->map(fn ($video) => [
                    'id' => $video->id,
                    'video_id' => $video->video_id,
                    'video_url' => $video->video_url,
                    'embed_url' => $video->embedUrl(),
                    'thumbnail_url' => $video->thumbnailUrl(),
                    'watch_url' => $video->watchUrl(),
                    'title' => $video->title,
                    'description' => $video->description,
                    'sort_order' => $video->sort_order,
                    'is_primary' => $video->is_primary,
                ]);
            }),

            // Relationships
            'category' => new CategoryResource($this->whenLoaded('category')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),

            // Variants
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'default_variant' => new ProductVariantResource($this->whenLoaded('defaultVariant')),

            // Inventory
            'inventory' => new InventoryResource($this->whenLoaded('inventory')),

            // Attributes
            'attributes' => $this->whenLoaded('attributeValues', function () {
                return $this->attributeValues->map(fn ($av) => [
                    'attribute' => [
                        'id' => $av->attribute->id,
                        'name' => $av->attribute->name,
                        'slug' => $av->attribute->slug,
                    ],
                    'value' => [
                        'id' => $av->attributeValue->id,
                        'value' => $av->attributeValue->value,
                        'slug' => $av->attributeValue->slug,
                    ],
                ]);
            }),

            // Reviews
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'reviews' => ProductReviewResource::collection($this->whenLoaded('reviews')),

            // Product Relations
            'related_products' => ProductResource::collection(
                $this->whenLoaded('relatedProducts', fn () => $this->relatedProducts->pluck('relatedProduct'))
            ),
            'cross_sell_products' => ProductResource::collection(
                $this->whenLoaded('crossSellProducts', fn () => $this->crossSellProducts->pluck('relatedProduct'))
            ),
            'up_sell_products' => ProductResource::collection(
                $this->whenLoaded('upSellProducts', fn () => $this->upSellProducts->pluck('relatedProduct'))
            ),

            // Pricing Tiers
            'pricing_tiers' => $this->whenLoaded('pricingTiers', function () {
                return $this->pricingTiers->whereNull('variant_id')->map(fn ($tier) => [
                    'id' => $tier->id,
                    'min_quantity' => $tier->min_quantity,
                    'max_quantity' => $tier->max_quantity,
                    'price' => $tier->price,
                    'customer_group_id' => $tier->customer_group_id,
                ]);
            }),

            // Collections
            'collections' => ProductCollectionResource::collection($this->whenLoaded('collections')),

            // SEO Data
            'seo' => $this->whenLoaded('seo', fn () => [
                'og_title' => $this->seo->og_title,
                'og_description' => $this->seo->og_description,
                'og_image' => $this->when($this->seo->ogImageMedia, fn () => [
                    'id' => $this->seo->ogImageMedia->id,
                    'url' => $this->seo->ogImageMedia->getUrl(),
                ]),
                'twitter_card' => $this->seo->twitter_card,
                'twitter_title' => $this->seo->twitter_title,
                'twitter_description' => $this->seo->twitter_description,
                'robots_meta' => $this->seo->robots_meta,
            ]),

            // -----------------------------------------------------------------
            // TYPE-SPECIFIC DATA
            // -----------------------------------------------------------------

            // Digital Product Fields
            'digital' => $this->when(
                $productType === ProductType::Digital,
                fn () => [
                    'download_limit' => $this->download_limit,
                    'download_expiry_days' => $this->download_expiry_days,
                    'preview_media' => $this->whenLoaded('previewMedia', fn (): array => [
                        'id' => $this->previewMedia?->id,
                        'file_name' => $this->previewMedia?->file_name,
                        'url' => $this->previewMedia?->getUrl(),
                    ]),
                    'files' => $this->whenLoaded('digitalFiles', function () {
                        return $this->digitalFiles->map(fn ($file) => [
                            'id' => $file->id,
                            'file_name' => $file->file_name,
                            'download_count' => $file->download_count,
                            'sort_order' => $file->sort_order,
                            'media' => [
                                'id' => $file->media->id,
                                'file_name' => $file->media->file_name,
                                'url' => $file->media->getUrl(),
                            ],
                        ]);
                    }),
                ]
            ),

            // Service Product Fields
            'service' => $this->when(
                $productType === ProductType::Service,
                fn () => [
                    'duration_minutes' => $this->duration_minutes,
                    'buffer_minutes' => $this->buffer_minutes,
                    'max_participants' => $this->max_participants,
                    'location_type' => $this->location_type,
                    'service_location' => $this->service_location,
                    'providers' => $this->whenLoaded('serviceProviders', function () {
                        return $this->serviceProviders->map(fn ($sp) => [
                            'id' => $sp->provider->id,
                            'name' => $sp->provider->name,
                            'email' => $sp->provider->email,
                            'is_primary' => $sp->is_primary,
                        ]);
                    }),
                ]
            ),

            // Combo Product Fields
            'combo' => $this->when(
                $productType === ProductType::Combo,
                fn () => [
                    'allow_partial_combo' => $this->allow_partial_combo,
                    'total_value' => $this->comboTotalValue(),
                    'savings' => $this->comboSavings(),
                    'items' => $this->whenLoaded('comboItems', function () {
                        return $this->comboItems->map(fn ($item) => [
                            'id' => $item->id,
                            'quantity' => $item->quantity,
                            'is_optional' => $item->is_optional,
                            'discount_percentage' => $item->discount_percentage,
                            'sort_order' => $item->sort_order,
                            'product' => [
                                'id' => $item->includedProduct->id,
                                'name' => $item->includedProduct->name,
                                'slug' => $item->includedProduct->slug,
                                'price' => $item->includedProduct->price,
                                'primary_image' => $this->when($item->includedProduct->primaryImageMedia, fn () => [
                                    'url' => $item->includedProduct->primaryImageMedia->getUrl(),
                                ]),
                            ],
                            'variant' => $this->when($item->includedVariant, fn () => [
                                'id' => $item->includedVariant->id,
                                'name' => $item->includedVariant->name,
                                'price' => $item->includedVariant->price,
                            ]),
                        ]);
                    }),
                ]
            ),

            // Analytics
            'view_count' => $this->view_count,

            // Structured data for SEO
            'structured_data' => $this->when($request->has('with_structured_data'), fn () => $this->toStructuredData()),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
