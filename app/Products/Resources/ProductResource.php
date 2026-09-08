<?php

namespace App\Products\Resources;

use App\Products\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'status' => $this->status,
            'image_path' => $this->image_path,
            'source_url' => $this->source_url,
            'total_stock' => (int) ($this->total_stock ?? $this->variants()->sum('stock_quantity')),
            'variants_count' => $this->variants_count ?? $this->variants()->count(),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
