<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Brand
 */
class BrandResource extends JsonResource
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
            'is_visible' => $this->is_visible,
            'logo' => $this->when(
                $this->relationLoaded('media') || $this->hasMedia('logo'),
                fn () => new MediaResource($this->getFirstMedia('logo')),
            ),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
