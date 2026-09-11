<?php

namespace Database\Seeders;

use App\Inventory\Actions\AdjustStockAction;
use App\Inventory\Enums\InventoryTransactionType;
use App\Models\User;
use App\Products\Enums\ProductStatus;
use App\Products\Models\Product;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', env('ARASHOP_OWNER_EMAIL', 'ale@arashop.test'))->first()
            ?? User::first();

        if ($owner === null) {
            return;
        }

        $products = [
            [
                'name' => 'Insulated Tumbler 40oz',
                'sku' => 'ARA-TUM-040',
                'status' => ProductStatus::Active,
                'variants' => [
                    ['name' => 'Rose', 'attributes' => ['Color' => 'Rose'], 'cost_usd' => 34.00, 'price_mxn' => 899.00, 'stock' => 12],
                    ['name' => 'Sage', 'attributes' => ['Color' => 'Sage'], 'cost_usd' => 34.00, 'price_mxn' => 899.00, 'stock' => 8],
                    ['name' => 'Lilac', 'attributes' => ['Color' => 'Lilac'], 'cost_usd' => 34.00, 'price_mxn' => 899.00, 'stock' => 15],
                ],
            ],
            [
                'name' => 'Insulated Water Bottle 24oz',
                'sku' => 'ARA-BOT-024',
                'status' => ProductStatus::Active,
                'variants' => [
                    ['name' => 'Cobalt', 'attributes' => ['Color' => 'Cobalt'], 'cost_usd' => 27.50, 'price_mxn' => 720.00, 'stock' => 20],
                    ['name' => 'Coral', 'attributes' => ['Color' => 'Coral'], 'cost_usd' => 27.50, 'price_mxn' => 720.00, 'stock' => 6],
                ],
            ],
            [
                'name' => 'Fragrance Mist 250ml',
                'sku' => 'ARA-FRM-250',
                'status' => ProductStatus::Active,
                'variants' => [
                    ['name' => 'Bare', 'attributes' => ['Scent' => 'Bare'], 'cost_usd' => 12.00, 'price_mxn' => 390.00, 'stock' => 25],
                    ['name' => 'Born Wild', 'attributes' => ['Scent' => 'Born Wild'], 'cost_usd' => 12.00, 'price_mxn' => 390.00, 'stock' => 18],
                ],
            ],
            [
                'name' => 'Varsity Bomber Jacket',
                'sku' => 'ARA-JKT-001',
                'status' => ProductStatus::Active,
                'variants' => [
                    ['name' => 'Black / S', 'attributes' => ['Color' => 'Black', 'Size' => 'S'], 'cost_usd' => 58.00, 'price_mxn' => 1390.00, 'stock' => 4],
                    ['name' => 'Black / M', 'attributes' => ['Color' => 'Black', 'Size' => 'M'], 'cost_usd' => 58.00, 'price_mxn' => 1390.00, 'stock' => 7],
                    ['name' => 'Black / L', 'attributes' => ['Color' => 'Black', 'Size' => 'L'], 'cost_usd' => 58.00, 'price_mxn' => 1390.00, 'stock' => 3],
                    ['name' => 'Black / XL', 'attributes' => ['Color' => 'Black', 'Size' => 'XL'], 'cost_usd' => 58.00, 'price_mxn' => 1390.00, 'stock' => 2],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::query()->firstOrCreate(
                ['sku' => $productData['sku']],
                ['name' => $productData['name'], 'status' => $productData['status']],
            );

            foreach ($productData['variants'] as $variantData) {
                $variant = $product->variants()->updateOrCreate(
                    ['name' => $variantData['name']],
                    [
                        'attributes' => $variantData['attributes'],
                        'cost_usd' => $variantData['cost_usd'],
                        'price_mxn' => $variantData['price_mxn'],
                        'stock_quantity' => $variantData['stock'],
                    ],
                );

                $hasInitial = $variant->transactions()
                    ->where('transaction_type', InventoryTransactionType::Restock)
                    ->where('reason', 'initial')
                    ->doesntExist();

                if ($hasInitial) {
                    app(AdjustStockAction::class)->handle(
                        variant: $variant,
                        quantityChange: $variantData['stock'],
                        type: InventoryTransactionType::Restock,
                        reason: 'initial',
                        notes: null,
                        user: $owner,
                    );
                }
            }
        }
    }
}
