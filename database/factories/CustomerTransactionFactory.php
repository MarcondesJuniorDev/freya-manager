<?php

namespace Database\Factories;

use App\Models\CustomerTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

use App\Models\Customer;
use App\Models\Sale;

/**
 * @extends Factory<CustomerTransaction>
 */
class CustomerTransactionFactory extends Factory
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
            'sale_id' => null,
            'type' => $this->faker->randomElement(['debit', 'credit']),
            'amount' => $this->faker->randomFloat(2, 10, 150),
            'transaction_date' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'description' => $this->faker->sentence(),
        ];
    }
}
