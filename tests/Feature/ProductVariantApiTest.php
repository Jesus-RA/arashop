<?php

namespace Tests\Feature;

use App\Models\User;
use App\Products\Models\Product;
use App\Products\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_variant_requires_authentication(): void
    {
        $product = Product::factory()->create();

        $this->postJson("/api/products/{$product->id}/variants", [
            'name' => 'White / M',
        ])->assertUnauthorized();
    }

    public function test_it_creates_a_variant_with_initial_stock(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/products/{$product->id}/variants", [
                'name' => 'White / M',
                'cost_usd' => 12.50,
                'price_mxn' => 320,
                'stock_quantity' => 10,
                'attributes' => ['Color' => 'White', 'Size' => 'M'],
            ])
            ->assertCreated();

        $variant = ProductVariant::query()->findOrFail($response->json('data.id'));

        $this->assertSame($product->id, $variant->product_id);
        $this->assertSame('White / M', $variant->name);
        $this->assertSame(10, $variant->stock_quantity);
        $this->assertSame('White', $variant->attributes['Color']);

        $this->assertDatabaseHas('stock_adjustments', [
            'variant_id' => $variant->id,
            'quantity_change' => 10,
            'reason' => 'initial',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_updates_a_variant_without_touching_stock(): void
    {
        $product = Product::factory()->create();
        $variant = $product->variants()->create(
            ProductVariant::factory()->make(['stock_quantity' => 7])->toArray(),
        );

        $this->actingAs($this->user)
            ->putJson("/api/products/{$product->id}/variants/{$variant->id}", [
                'name' => 'White / L',
                'price_mxn' => 350,
            ])
            ->assertOk();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'name' => 'White / L',
            'price_mxn' => 350,
            'stock_quantity' => 7,
        ]);

        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_it_adjusts_stock_when_quantity_changes_on_update(): void
    {
        $product = Product::factory()->create();
        $variant = $product->variants()->create(
            ProductVariant::factory()->make(['stock_quantity' => 3])->toArray(),
        );

        $this->actingAs($this->user)
            ->putJson("/api/products/{$product->id}/variants/{$variant->id}", [
                'name' => 'White / L',
                'stock_quantity' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('data.stock_quantity', 10);

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id,
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'variant_id' => $variant->id,
            'quantity_change' => 7,
            'reason' => 'adjustment',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_soft_deletes_a_variant(): void
    {
        $product = Product::factory()->create();
        $variant = $product->variants()->create(
            ProductVariant::factory()->make()->toArray(),
        );

        $this->actingAs($this->user)
            ->deleteJson("/api/products/{$product->id}/variants/{$variant->id}")
            ->assertOk();

        $this->assertSoftDeleted('product_variants', ['id' => $variant->id]);
    }

    public function test_it_requires_a_variant_name(): void
    {
        $product = Product::factory()->create();

        $this->actingAs($this->user)
            ->postJson("/api/products/{$product->id}/variants", [
                'cost_usd' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }
}
