<?php

namespace App\Inventory\Controllers;

use App\Inventory\Actions\ApplyStockAdjustment;
use App\Inventory\Requests\AdjustStockRequest;
use App\Inventory\Resources\StockAdjustmentResource;
use App\Products\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StockController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $variants = ProductVariant::query()
            ->with('product:id,name,sku')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('stock_quantity')
            ->paginate((int) $request->integer('per_page', 20));

        return StockAdjustmentResource::collection($variants);
    }

    public function history(Request $request, ProductVariant $variant): AnonymousResourceCollection
    {
        $adjustments = $variant->adjustments()
            ->with('user:id,name')
            ->paginate((int) $request->integer('per_page', 10));

        return StockAdjustmentResource::collection($adjustments);
    }

    public function store(AdjustStockRequest $request): Response
    {
        $variant = ProductVariant::findOrFail($request->integer('variant_id'));

        app(ApplyStockAdjustment::class)->handle(
            variant: $variant,
            quantityChange: $request->integer('quantity_change'),
            reason: $request->string('reason'),
            notes: $request->input('notes'),
            user: $request->user(),
        );

        return response()->noContent();
    }
}
