<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class HostFactory extends Factory
{
    public function definition(): array
    {
        $plaintext = Str::random(48);

        return [
            'label'                   => fake()->unique()->word(),
            'description'             => fake()->optional()->sentence(),
            'ip'                      => fake()->optional()->ipv4(),
            'api_key'                 => bcrypt($plaintext),
            'api_key_prefix'          => substr($plaintext, 0, 12),
            'last_seen_at'            => null,
            'last_offline_notified_at' => null,
        ];
    }

    public function withApiKey(string $plaintext): static
    {
        return $this->state([
            'api_key'        => bcrypt($plaintext),
            'api_key_prefix' => substr($plaintext, 0, 12),
        ]);
    }

    public function online(): static
    {
        return $this->state([
            'last_seen_at' => now()->subMinutes(1),
        ]);
    }

    public function offline(int $minutesAgo = 10): static
    {
        return $this->state([
            'last_seen_at' => now()->subMinutes($minutesAgo),
        ]);
    }
}
