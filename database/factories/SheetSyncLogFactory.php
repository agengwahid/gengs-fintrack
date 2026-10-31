<?php

namespace Database\Factories;

use App\Models\SheetSyncLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SheetSyncLog>
 */
class SheetSyncLogFactory extends Factory
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
            'transaction_id' => Transaction::factory(),
            'action' => fake()->randomElement(['create', 'update', 'delete']),
            'status' => 'pending',
            'attempts' => 1,
            'last_error' => null,
            'synced_at' => null,
        ];
    }

    /**
     * Indicate that the sync succeeded.
     */
    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'synced_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Indicate that the sync failed.
     */
    public function failed(?string $error = 'Failed to connect to Google Sheets'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'attempts' => 3,
            'last_error' => $error,
            'synced_at' => null,
        ]);
    }
}
