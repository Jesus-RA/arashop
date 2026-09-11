<?php

namespace Tests\Feature;

use App\Inventory\Enums\InventoryTransactionType;
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

    public function test_it_applies_an_inventory_adjustment(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/inventory/adjust', [
                'variant_id' => $this->variant->id,
                'quantity_change' => -4,
                'transaction_type' => InventoryTransactionType::Sale->value,
                'notes' => 'Sold two shirts',
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 6,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'variant_id' => $this->variant->id,
            'quantity_change' => -4,
            'transaction_type' => InventoryTransactionType::Sale->value,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_rejects_zero_quantity_changes(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/inventory/adjust', [
                'variant_id' => $this->variant->id,
                'quantity_change' => 0,
                'transaction_type' => InventoryTransactionType::ManualAdjustment->value,
                'reason' => 'adjustment',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity_change']);
    }

    public function test_it_requires_a_reason_for_manual_adjustments(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/inventory/adjust', [
                'variant_id' => $this->variant->id,
                'quantity_change' => 3,
                'transaction_type' => InventoryTransactionType::ManualAdjustment->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_it_rejects_invalid_transaction_types(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/inventory/adjust', [
                'variant_id' => $this->variant->id,
                'quantity_change' => 3,
                'transaction_type' => 'SOMETHING_ELSE',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['transaction_type']);
    }

    public function test_it_applies_a_restock_without_a_reason(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/inventory/adjust', [
                'variant_id' => $this->variant->id,
                'quantity_change' => 5,
                'transaction_type' => InventoryTransactionType::Restock->value,
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 15,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'variant_id' => $this->variant->id,
            'quantity_change' => 5,
            'transaction_type' => InventoryTransactionType::Restock->value,
        ]);
    }

    public function test_it_prevents_stock_from_going_negative(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/inventory/adjust', [
                'variant_id' => $this->variant->id,
                'quantity_change' => -11,
                'transaction_type' => InventoryTransactionType::Sale->value,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['quantity_change']);

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_it_returns_transaction_history(): void
    {
        $this->variant->transactions()->create([
            'quantity_change' => -3,
            'transaction_type' => InventoryTransactionType::Sale->value,
            'user_id' => $this->user->id,
        ]);
        $this->variant->transactions()->create([
            'quantity_change' => 5,
            'transaction_type' => InventoryTransactionType::Restock->value,
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->getJson("/api/variants/{$this->variant->id}/transactions")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.transaction_type', InventoryTransactionType::Restock->value);
    }
}
