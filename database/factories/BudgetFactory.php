<?php

namespace Database\Factories;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->state(['type' => 'expense']),
            'period' => fake()->dateTimeThisYear()->format('Y-m'),
            'amount' => fake()->randomElement([500000, 1000000, 1500000, 2000000, 3000000]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
