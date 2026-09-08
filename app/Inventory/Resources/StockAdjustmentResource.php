<?php

namespace App\Inventory\Resources;

use App\Inventory\Models\StockAdjustment;
use App\Products\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProductVariant|StockAdjustment */
class StockAdjustmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->resource->id,
        ];

        if ($this->resource instanceof StockAdjustment) {
            $data['variant_id'] = $this->resource->variant_id;
            $data['quantity_change'] = $this->resource->quantity_change;
            $data['reason'] = $this->resource->reason;
            $data['notes'] = $this->resource->notes;
            $data['user'] = $this->whenLoaded('user', fn () => [
                'id' => $this->resource->user?->id,
                'name' => $this->resource->user?->name,
            ]);
            $data['created_at'] = $this->resource->created_at;
        } else {
            $data['product_id'] = $this->resource->product_id;
            $data['product_name'] = $this->whenLoaded('product', fn () => $this->resource->product?->name);
            $data['product_sku'] = $this->whenLoaded('product', fn () => $this->resource->product?->sku);
            $data['name'] = $this->resource->name;
            $data['sku'] = $this->resource->sku;
            $data['attributes'] = $this->resource->attributes;
            $data['cost_usd'] = $this->resource->cost_usd;
            $data['price_mxn'] = $this->resource->price_mxn;
            $data['stock_quantity'] = $this->resource->stock_quantity;
        }

        return $data;
    }
}
