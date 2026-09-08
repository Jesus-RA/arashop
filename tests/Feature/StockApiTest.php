<?php

namespace Tests\Feature;

use App\Models\User;
use App\Products\Models\Product;
use App\Products\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create();
        $this->variant = $this->product->variants()->create(
            ProductVariant::factory()->make(['stock_quantity' => 10])->toArray(),
        );
    }

    public function test_it_returns_all_variants_with_product(): void
    {
        $this->actingAs($this->user)
            ->getJson('/api/stock')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.product_name', $this->product->name);
    }

    public function test_it_applies_a_stock_adjustment(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/stock/adjustments', [
                'variant_id' => $this->variant->id,
                'quantity_change' => -4,
                'reason' => 'sale',
                'notes' => 'Sold two shirts',
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 6,
        ]);

        $this->assertDatabaseHas('stock_adjustments', [
            'variant_id' => $this->variant->id,
            'quantity_change' => -4,
            'reason' => 'sale',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_rejects_zero_quantity_changes(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/stock/adjustments', [
                'variant_id' => $this->variant->id,
                'quantity_change' => 0,
                'reason' => 'adjustment',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity_change']);
    }

    public function test_it_prevents_stock_from_going_negative(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/stock/adjustments', [
                'variant_id' => $this->variant->id,
                'quantity_change' => -11,
                'reason' => 'sale',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity_change']);

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_it_returns_adjustment_history(): void
    {
        $this->variant->adjustments()->create([
            'quantity_change' => -3,
            'reason' => 'sale',
            'user_id' => $this->user->id,
        ]);
        $this->variant->adjustments()->create([
            'quantity_change' => 5,
            'reason' => 'purchase',
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/stock/{$this->variant->id}/history")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
