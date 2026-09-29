<?php

namespace Database\Factories;

use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city().' Server',
            'api_url' => 'https://'.fake()->domainName().':'.fake()->numberBetween(8000, 9999),
            'cert_sha256' => fake()->optional()->sha256(),
        ];
    }
}
