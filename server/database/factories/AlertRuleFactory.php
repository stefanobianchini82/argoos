<?php

namespace Database\Factories;

use App\Models\AlertRule;
use App\Models\Host;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'host_id'          => Host::factory(),
            'metric'           => fake()->randomElement(array_keys(AlertRule::METRICS)),
            'operator'         => fake()->randomElement(AlertRule::OPERATORS),
            'threshold'        => fake()->randomFloat(1, 10, 90),
            'duration_minutes' => fake()->randomElement([1, 5, 10, 15]),
            'channel'          => fake()->randomElement(AlertRule::CHANNELS),
            'channel_target'   => fake()->email(),
            'is_active'        => true,
            'last_notified_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function forMetric(string $metric, string $operator, float $threshold): static
    {
        return $this->state([
            'metric'    => $metric,
            'operator'  => $operator,
            'threshold' => $threshold,
        ]);
    }
}
