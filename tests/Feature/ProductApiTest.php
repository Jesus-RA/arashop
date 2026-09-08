<?php

namespace Tests\Feature;

use App\Models\User;
use App\Products\Enums\ProductStatus;
use App\Products\Models\Product;
use App\Products\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_product_requires_authentication(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }

    public function test_it_lists_products_with_totals(): void
    {
        $product = Product::factory()->create();
        $product->variants()->saveMany([
            ProductVariant::factory()->make(['product_id' => $product->id, 'stock_quantity' => 5]),
            ProductVariant::factory()->make(['product_id' => $product->id, 'stock_quantity' => 3]),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/products')
            ->assertOk();

        $response->assertJsonPath('data.0.id', $product->id)
            ->assertJsonPath('data.0.total_stock', 8)
            ->assertJsonPath('data.0.variants_count', 2);
    }

    public function test_it_searches_products_by_name_and_sku(): void
    {
        Product::factory()->create(['name' => 'Amazing Tee', 'sku' => 'ARA-TEE-1']);
        Product::factory()->create(['name' => 'Plain Hoodie', 'sku' => 'ARA-HOO-1']);

        $this->actingAs($this->user)
            ->getJson('/api/products?search=tee')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Amazing Tee');

        $this->actingAs($this->user)
            ->getJson('/api/products?search=ARA-HOO-1')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_it_creates_a_product_with_variant_and_initial_stock(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/products', [
                'name' => 'New Tee',
                'sku' => 'ARA-NEW-1',
                'status' => ProductStatus::Active->value,
                'variants' => [
                    [
                        'name' => 'White / M',
                        'sku' => 'ARA-NEW-1-WM',
                        'cost_usd' => 12.50,
                        'price_mxn' => 320,
                        'stock_quantity' => 10,
                        'attributes' => ['Color' => 'White', 'Size' => 'M'],
                    ],
                ],
            ])
            ->assertCreated();

        $product = Product::query()->findOrFail($response->json('data.id'));

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'name' => 'White / M',
            'stock_quantity' => 10,
        ]);

        $variant = $product->variants()->first();

        $this->assertDatabaseHas('stock_adjustments', [
            'variant_id' => $variant->id,
            'quantity_change' => 10,
            'reason' => 'initial',
        ]);
    }

    public function test_it_validates_variant_requirements(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/products', [
                'name' => 'Missing Variants',
                'status' => ProductStatus::Draft->value,
                'variants' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variants']);
    }

    public function test_it_updates_a_product_price_without_touching_stock(): void
    {
        $product = Product::factory()->create();
        $variant = $product->variants()->create(
            ProductVariant::factory()->make(['stock_quantity' => 7])->toArray(),
        );

        $this->actingAs($this->user)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Renamed Product',
                'status' => ProductStatus::Active->value,
                'variants' => [
                    [
                        'id' => $variant->id,
                        'name' => 'White / M',
                        'price_mxn' => 350,
                    ],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Renamed Product',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'price_mxn' => 350,
            'stock_quantity' => 7,
        ]);
    }

    public function test_it_soft_deletes_a_product(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->deleteJson("/api/products/{$product->id}")
            ->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }
}
