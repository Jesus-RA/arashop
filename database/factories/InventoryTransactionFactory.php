<?php

namespace Database\Factories;

use App\Inventory\Enums\InventoryTransactionType;
use App\Inventory\Models\InventoryTransaction;
use App\Models\User;
use App\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryTransaction>
 */
class InventoryTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'variant_id' => ProductVariant::factory(),
            'quantity_change' => fake()->numberBetween(-10, 25),
            'transaction_type' => fake()->randomElement(InventoryTransactionType::values()),
            'reference_id' => null,
            'reason' => fake()->optional()->randomElement(['initial', 'purchase', 'sale', 'damaged', 'adjustment', 'return']),
            'notes' => fake()->sentence(),
            'user_id' => User::factory(),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function sale(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => InventoryTransactionType::Sale,
            'quantity_change' => -abs($attributes['quantity_change'] ?: 1),
        ]);
    }

    public function restock(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => InventoryTransactionType::Restock,
            'quantity_change' => abs($attributes['quantity_change'] ?: 1),
        ]);
    }

    public function orderCancellation(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => InventoryTransactionType::OrderCancellation,
            'quantity_change' => abs($attributes['quantity_change'] ?: 1),
        ]);
    }
}
