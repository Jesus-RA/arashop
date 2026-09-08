<?php

namespace Database\Factories;

use App\Inventory\Models\StockAdjustment;
use App\Models\User;
use App\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAdjustment>
 */
class StockAdjustmentFactory extends Factory
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
            'reason' => fake()->randomElement(['initial', 'purchase', 'sale', 'damaged', 'adjustment']),
            'notes' => fake()->sentence(),
            'user_id' => User::factory(),
        ];
    }
}
