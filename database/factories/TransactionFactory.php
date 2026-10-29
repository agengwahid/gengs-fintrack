<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['expense', 'income']);

        return [
            'user_id' => User::factory(),
            'account_id' => Account::factory(),
            'destination_account_id' => null,
            'category_id' => Category::factory(),
            'type' => $type,
            'amount' => fake()->numberBetween(10000, 500000),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'notes' => fake()->sentence(),
            'reference_number' => null,
        ];
    }
}
