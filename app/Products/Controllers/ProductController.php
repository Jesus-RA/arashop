<?php

namespace App\Products\Controllers;

use App\Inventory\Actions\ApplyStockAdjustment;
use App\Products\Actions\ImportProducts;
use App\Products\Models\Product;
use App\Products\Requests\ImportProductsRequest;
use App\Products\Requests\StoreProductRequest;
use App\Products\Requests\UpdateProductRequest;
use App\Products\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->withCount('variants')
            ->withSum('variants as total_stock', 'stock_quantity')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->orderBy('name')
            ->paginate((int) $request->integer('per_page', 15));

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): ProductResource
    {
        $product = Product::create($request->safe()->except('variants'));

        $this->createVariants($product, $request->validated('variants'));

        return new ProductResource($product->load('variants'));
    }

    public function show(Product $product): ProductResource
    {
        return new ProductResource(
            $product->load('variants')->loadSum('variants as total_stock', 'stock_quantity')
        );
    }

    public function update(UpdateProductRequest $request, Product $product): ProductResource
    {
        $product->update($request->safe()->except(['variants']));

        $this->syncVariants($product, $request->validated('variants'));

        return new ProductResource($product->load('variants'));
    }

    public function import(ImportProductsRequest $request): JsonResponse
    {
        $result = app(ImportProducts::class)->handle($request->file('file'));

        return response()->json($result);
    }

    public function importPreview(ImportProductsRequest $request): JsonResponse
    {
        $result = app(ImportProducts::class)->preview($request->file('file'));

        return response()->json($result);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['ok' => true], 200);
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function createVariants(Product $product, array $variants): void
    {
        $applyStockAdjustment = app(ApplyStockAdjustment::class);

        foreach ($variants as $variantData) {
            $stock = (int) ($variantData['stock_quantity'] ?? 0);

            $variant = $product->variants()->create([
                'sku' => $variantData['sku'] ?? null,
                'name' => $variantData['name'],
                'attributes' => $variantData['attributes'] ?? null,
                'cost_usd' => $variantData['cost_usd'] ?? null,
                'price_mxn' => $variantData['price_mxn'] ?? null,
                'stock_quantity' => 0,
            ]);

            if ($stock > 0) {
                $applyStockAdjustment->handle($variant, $stock, 'initial', 'Initial stock', auth()->user());
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $sentVariantIds = collect($variants)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        $product->variants()
            ->whereNotIn('id', $sentVariantIds)
            ->get()
            ->each->delete();

        foreach ($variants as $variantData) {
            if (! empty($variantData['id'])) {
                $variant = $product->variants()->find($variantData['id']);

                if ($variant !== null) {
                    $variant->update([
                        'sku' => $variantData['sku'] ?? null,
                        'name' => $variantData['name'],
                        'attributes' => $variantData['attributes'] ?? null,
                        'cost_usd' => $variantData['cost_usd'] ?? null,
                        'price_mxn' => $variantData['price_mxn'] ?? null,
                    ]);

                    continue;
                }
            }

            $product->variants()->create([
                'sku' => $variantData['sku'] ?? null,
                'name' => $variantData['name'],
                'attributes' => $variantData['attributes'] ?? null,
                'cost_usd' => $variantData['cost_usd'] ?? null,
                'price_mxn' => $variantData['price_mxn'] ?? null,
                'stock_quantity' => 0,
            ]);
        }
    }
}
