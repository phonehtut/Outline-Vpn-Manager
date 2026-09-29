<?php

namespace Database\Factories;

use App\Models\AccessKey;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AccessKey>
 */
class AccessKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'created_by' => User::factory(),
            'outline_key_id' => Str::random(8),
            'name' => fake()->firstName()."'s VPN",
            'data_limit_bytes' => fake()->optional()->numberBetween(1_073_741_824, 107_374_182_400),
            'expires_at' => fake()->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }

    /**
     * Mark this key as already expired.
     */
    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDays(fake()->numberBetween(1, 30))]);
    }

    /**
     * Mark this key as expiring soon (within 3 days).
     */
    public function expiringSoon(): static
    {
        return $this->state(['expires_at' => now()->addDays(fake()->numberBetween(1, 2))]);
    }

    /**
     * Mark this key as having no expiry.
     */
    public function noExpiry(): static
    {
        return $this->state(['expires_at' => null]);
    }
}
