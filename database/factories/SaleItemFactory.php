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
            'quantity' => $quantity = $this->faker->numberBetween(1, 3),
            'unit_price' => function (array $attributes) {
                return Product::find($attributes['product_id'])->sale_price;
            },
            'unit_cost' => function (array $attributes) {
                return Product::find($attributes['product_id'])->cost_price;
            },
            'subtotal' => function (array $attributes) use ($quantity) {
                $price = Product::find($attributes['product_id'])->sale_price;
                return $price * $attributes['quantity'];
            },
        ];
    }
}
