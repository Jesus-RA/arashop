<?php

namespace App\Orders\Resources;

use App\Orders\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price_mxn' => $this->unit_price_mxn,
            'unit_cost_usd' => $this->unit_cost_usd,
            'subtotal' => $this->subtotal,
            'variant' => $this->whenLoaded('variant', fn () => [
                'id' => $this->variant?->id,
                'name' => $this->variant?->name,
                'sku' => $this->variant?->sku,
                'attributes' => $this->variant?->attributes,
            ]),
        ];
    }
}
