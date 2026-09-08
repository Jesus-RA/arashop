<?php

namespace Database\Factories;

use App\Products\Models\Product;
use App\Products\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => Str::upper(fake()->bothify('ARA-####-??')),
            'name' => fake()->words(2, true),
            'attributes' => ['Color' => fake()->colorName()],
            'cost_usd' => fake()->randomFloat(2, 2, 80),
            'price_mxn' => fake()->randomFloat(2, 99, 1999),
            'stock_quantity' => fake()->numberBetween(0, 100),
            'image_path' => null,
        ];
    }
}
