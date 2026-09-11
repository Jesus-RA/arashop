<?php

namespace App\Inventory\Controllers;

use App\Inventory\Actions\AdjustStockAction;
use App\Inventory\Enums\InventoryTransactionType;
use App\Inventory\Requests\AdjustStockRequest;
use App\Inventory\Resources\InventoryTransactionResource;
use App\Inventory\Resources\StockVariantResource;
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

        return StockVariantResource::collection($variants);
    }

    public function transactions(Request $request, ProductVariant $variant): AnonymousResourceCollection
    {
        $transactions = $variant->transactions()
            ->with('user:id,name')
            ->paginate((int) $request->integer('per_page', 50));

        return InventoryTransactionResource::collection($transactions);
    }

    public function adjust(AdjustStockRequest $request): Response
    {
        $variant = ProductVariant::findOrFail($request->integer('variant_id'));

        app(AdjustStockAction::class)->handle(
            variant: $variant,
            quantityChange: $request->integer('quantity_change'),
            type: InventoryTransactionType::from($request->string('transaction_type')),
            reason: $request->input('reason'),
            notes: $request->input('notes'),
            user: $request->user(),
        );

        return response()->noContent();
    }
}
