<?php

namespace Database\Factories;

use App\Models\AlertRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlertEventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'alert_rule_id' => AlertRule::factory(),
            'triggered_at'  => now()->subMinutes(5),
            'resolved_at'   => null,
            'peak_value'    => fake()->randomFloat(1, 50, 95),
        ];
    }

    public function resolved(): static
    {
        return $this->state([
            'resolved_at' => now(),
        ]);
    }
}
