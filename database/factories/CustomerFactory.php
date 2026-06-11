<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'balance' => 0.00, // Best left at 0 by default, updated via seeder/transaction logic
            'max_credit_limit' => $this->faker->randomElement([300.00, 500.00, 1000.00]),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
