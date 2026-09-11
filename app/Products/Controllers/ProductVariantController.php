<?php

namespace App\Products\Controllers;

use App\Inventory\Actions\ApplyStockAdjustment;
use App\Products\Models\Product;
use App\Products\Models\ProductVariant;
use App\Products\Requests\StoreProductVariantRequest;
use App\Products\Requests\UpdateProductVariantRequest;
use App\Products\Resources\ProductVariantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController
{
    public function store(StoreProductVariantRequest $request, Product $product): ProductVariantResource
    {
        $variant = $product->variants()->create([
            'sku' => $request->validated('sku'),
            'name' => $request->validated('name'),
            'attributes' => $request->validated('attributes'),
            'cost_usd' => $request->validated('cost_usd'),
            'price_mxn' => $request->validated('price_mxn'),
            'stock_quantity' => 0,
        ]);

        $stock = (int) $request->integer('stock_quantity');

        if ($stock > 0) {
            app(ApplyStockAdjustment::class)->handle($variant, $stock, 'initial', 'Initial stock', auth()->user());
        }

        return new ProductVariantResource($variant);
    }

    public function update(UpdateProductVariantRequest $request, Product $product, ProductVariant $variant): ProductVariantResource
    {
        $currentStock = $variant->stock_quantity;
        $targetStock = (int) ($request->validated('stock_quantity') ?? $currentStock);

        $variant->update([
            'sku' => $request->validated('sku'),
            'name' => $request->validated('name'),
            'attributes' => $request->validated('attributes'),
            'cost_usd' => $request->validated('cost_usd'),
            'price_mxn' => $request->validated('price_mxn'),
        ]);

        if ($targetStock !== $currentStock) {
            app(ApplyStockAdjustment::class)->handle(
                variant: $variant,
                quantityChange: $targetStock - $currentStock,
                reason: 'adjustment',
                notes: 'Variant edit',
                user: auth()->user(),
            );
        }

        return new ProductVariantResource($variant);
    }

    public function destroy(Request $request, Product $product, ProductVariant $variant): JsonResponse
    {
        $variant->delete();

        return response()->json(['ok' => true], 200);
    }
}
