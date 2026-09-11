<?php

namespace App\Inventory\Resources;

use App\Products\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant */
class StockVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn () => $this->product?->name),
            'product_sku' => $this->whenLoaded('product', fn () => $this->product?->sku),
            'name' => $this->name,
            'sku' => $this->sku,
            'attributes' => $this->attributes,
            'cost_usd' => $this->cost_usd,
            'price_mxn' => $this->price_mxn,
            'stock_quantity' => $this->stock_quantity,
        ];
    }
}
