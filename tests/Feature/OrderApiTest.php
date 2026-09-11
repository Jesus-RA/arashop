<?php

namespace Tests\Feature;

use App\Inventory\Enums\InventoryTransactionType;
use App\Models\User;
use App\Orders\Models\Order;
use App\Orders\Models\OrderItem;
use App\Products\Models\Product;
use App\Products\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
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
            ProductVariant::factory()->make([
                'name' => 'White / M',
                'stock_quantity' => 10,
                'price_mxn' => 299.00,
                'cost_usd' => 12.50,
            ])->toArray(),
        );
    }

    public function test_orders_require_authentication(): void
    {
        $this->postJson('/api/orders', ['items' => []])->assertUnauthorized();
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_it_creates_an_order_and_deducts_stock(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'items' => [
                    ['variant_id' => $this->variant->id, 'quantity' => 2],
                ],
            ])
            ->assertCreated();

        $orderId = $response->json('data.id');

        $this->assertSame('ORD-0001', $response->json('data.order_number'));
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertSame('598.00', $response->json('data.total_amount'));

        $order = Order::query()->findOrFail($orderId);

        $this->assertSame(2, $order->items->sum('quantity'));

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'variant_id' => $this->variant->id,
            'quantity' => 2,
            'unit_price_mxn' => 299.00,
            'unit_cost_usd' => 12.50,
            'subtotal' => 598.00,
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 8,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'variant_id' => $this->variant->id,
            'quantity_change' => -2,
            'transaction_type' => InventoryTransactionType::Sale->value,
            'reference_id' => $order->id,
        ]);
    }

    public function test_it_rolls_back_when_stock_is_insufficient(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'items' => [
                    ['variant_id' => $this->variant->id, 'quantity' => 11],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.*.quantity']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 10,
        ]);
    }

    public function test_combined_duplicate_lines_cannot_oversell(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'items' => [
                    ['variant_id' => $this->variant->id, 'quantity' => 6],
                    ['variant_id' => $this->variant->id, 'quantity' => 6],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.*.quantity']);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('inventory_transactions', 0);
        $this->assertDatabaseHas('product_variants', ['id' => $this->variant->id, 'stock_quantity' => 10]);
    }

    public function test_prices_are_snapshotted_at_order_time(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'variant_id' => $this->variant->id,
            'quantity' => 1,
            'unit_price_mxn' => 299.00,
            'unit_cost_usd' => 12.50,
            'subtotal' => 299.00,
        ]);

        $this->variant->update(['price_mxn' => 499.00, 'cost_usd' => 20.00]);

        $this->actingAs($this->user)
            ->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.items.0.unit_price_mxn', '299.00')
            ->assertJsonPath('data.items.0.unit_cost_usd', '12.50')
            ->assertJsonPath('data.items.0.subtotal', '299.00');
    }

    public function test_it_lists_orders_with_status_filter(): void
    {
        Order::factory()->create(['status' => 'pending', 'user_id' => $this->user->id]);
        Order::factory()->create(['status' => 'paid', 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/orders?status=paid')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame('paid', $response->json('data.0.status'));
    }

    public function test_it_validates_order_items(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/orders', ['items' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items']);

        $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'items' => [['variant_id' => 999999, 'quantity' => 1]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.variant_id']);

        $this->actingAs($this->user)
            ->postJson('/api/orders', [
                'items' => [['variant_id' => $this->variant->id, 'quantity' => 0]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_cancelling_an_order_restores_stock(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'variant_id' => $this->variant->id,
            'quantity' => 2,
            'subtotal' => 598.00,
        ]);
        $this->variant->update(['stock_quantity' => 8]);

        $this->actingAs($this->user)
            ->patchJson("/api/orders/{$order->id}", ['status' => 'cancelled'])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('product_variants', [
            'id' => $this->variant->id,
            'stock_quantity' => 10,
        ]);

        $this->assertDatabaseHas('inventory_transactions', [
            'variant_id' => $this->variant->id,
            'quantity_change' => 2,
            'transaction_type' => InventoryTransactionType::OrderCancellation->value,
            'reference_id' => $order->id,
        ]);
    }

    public function test_shipped_status_is_not_accepted(): void
    {
        $order = Order::factory()->create(['status' => 'paid', 'user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patchJson("/api/orders/{$order->id}", ['status' => 'shipped'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    public function test_cancelled_orders_cannot_be_reopened(): void
    {
        $order = Order::factory()->create(['status' => 'cancelled', 'user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->patchJson("/api/orders/{$order->id}", ['status' => 'paid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
    }
}
