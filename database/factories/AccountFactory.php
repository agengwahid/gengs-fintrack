<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $initialBalance = fake()->numberBetween(100000, 10000000);

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['BCA', 'Mandiri', 'BRI', 'BNI', 'GoPay', 'OVO', 'ShopeePay', 'Dompet Tunai']),
            'type' => fake()->randomElement(['cash', 'bank', 'ewallet', 'investment']),
            'account_number' => fake()->numerify('##########'),
            'color' => fake()->randomElement(['#10b981', '#06b6d4', '#2563eb', '#8b5cf6', '#f59e0b']),
            'icon' => fake()->randomElement(['Wallet', 'Building2', 'Smartphone', 'CreditCard']),
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
