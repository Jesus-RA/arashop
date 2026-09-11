<?php

namespace Database\Factories;

use App\Models\User;
use App\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'ORD-'.str_pad((string) fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'pending',
            'total_amount' => fake()->randomFloat(2, 100, 5000),
            'user_id' => User::factory(),
        ];
    }
}
