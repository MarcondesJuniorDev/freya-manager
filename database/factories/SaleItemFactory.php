<?php

namespace Database\Factories;

use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Sale;
use App\Models\Product;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 3),
            'unit_price' => function (array $attributes) {
                return Product::where('id', $attributes['product_id'])->firstOrFail()->sale_price;
            },
            'unit_cost' => function (array $attributes) {
                return Product::where('id', $attributes['product_id'])->firstOrFail()->cost_price;
            },
            'subtotal' => function (array $attributes) {
                $price = (float) Product::where('id', $attributes['product_id'])->firstOrFail()->sale_price;
                return $price * $attributes['quantity'];
            },
        ];
    }
}
