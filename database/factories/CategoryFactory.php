<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
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
            'name' => fake()->randomElement(['Makan', 'Bensin', 'Belanja', 'Gaji', 'Bonus', 'Tagihan', 'Hiburan']),
            'type' => $type,
            'color' => $type === 'expense' ? '#ef4444' : '#10b981',
            'icon' => $type === 'expense' ? 'ShoppingCart' : 'Briefcase',
            'description' => fake()->sentence(),
            'is_default' => false,
        ];
    }
}
