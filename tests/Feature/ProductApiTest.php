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

    public function test_it_creates_a_product_without_variants(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/products', [
                'name' => 'New Tee',
                'status' => ProductStatus::Draft->value,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('products', [
            'id' => $response->json('data.id'),
            'name' => 'New Tee',
            'status' => ProductStatus::Draft->value,
        ]);

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_it_requires_a_product_name(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/products', [
                'status' => ProductStatus::Draft->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_it_updates_product_details_only(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->putJson("/api/products/{$product->id}", [
                'name' => 'Renamed Product',
                'status' => ProductStatus::Active->value,
            ])
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Renamed Product',
            'status' => ProductStatus::Active->value,
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
