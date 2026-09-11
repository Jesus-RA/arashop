<?php

namespace Database\Factories;

use App\Orders\Models\Order;
use App\Orders\Models\OrderItem;
use App\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 99, 1999);
        $quantity = fake()->numberBetween(1, 5);

        return [
            'order_id' => Order::factory(),
            'variant_id' => ProductVariant::factory(),
            'quantity' => $quantity,
            'unit_price_mxn' => $unitPrice,
            'unit_cost_usd' => fake()->randomFloat(2, 2, 80),
            'subtotal' => round($unitPrice * $quantity, 2),
        ];
    }
}
