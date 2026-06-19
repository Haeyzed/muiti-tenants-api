<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Category
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'parent_id' => $this->parent_id,
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'image' => $this->when(
                $this->relationLoaded('media') || $this->hasMedia('image'),
                fn () => new MediaResource($this->getFirstMedia('image')),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
