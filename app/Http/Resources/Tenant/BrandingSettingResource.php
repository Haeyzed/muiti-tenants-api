<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use App\Models\Tenant\BrandingSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BrandingSetting
 */
class BrandingSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'theme' => $this->theme,
            'store_logo' => $this->whenLoaded('media', fn () => new MediaResource($this->getFirstMedia('store_logo'))),
            'store_banner' => $this->whenLoaded('media', fn () => new MediaResource($this->getFirstMedia('store_banner'))),
            'favicon' => $this->whenLoaded('media', fn () => new MediaResource($this->getFirstMedia('favicon'))),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
