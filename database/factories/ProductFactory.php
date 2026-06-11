<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Brand;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        $costPrice = $this->faker->randomFloat(2, 10, 100);
        $catalogPrice = round($costPrice * $this->faker->randomFloat(2, 1.3, 1.8), 2);

        return [
            'brand_id' => Brand::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->paragraph(),
            'ean' => $this->faker->unique()->ean13(),
            'sku' => strtoupper($this->faker->unique()->bothify('???-#####')),
            'cost_price' => $costPrice,
            'catalog_price' => $catalogPrice,
            'sale_price' => $catalogPrice,
            'stock_quantity' => $this->faker->numberBetween(0, 20),
            'min_stock_quantity' => $this->faker->numberBetween(2, 5),
            'image_path' => null,
            'is_active' => true,
        ];
    }
}
