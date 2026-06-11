<?php

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Customer;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'sale_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'subtotal_amount' => 0.00, // Calculated dynamically when creating items
            'discount_amount' => 0.00,
            'total_amount' => 0.00,
            'total_cost' => 0.00,
            'payment_status' => $this->faker->randomElement(['paid', 'pending', 'partial']),
            'payment_method' => $this->faker->randomElement(['cash', 'card', 'pix', 'fiado']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
