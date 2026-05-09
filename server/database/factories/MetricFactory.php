<?php

namespace Database\Factories;

use App\Models\Host;
use Illuminate\Database\Eloquent\Factories\Factory;

class MetricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'host_id'          => Host::factory(),
            'collected_at'     => now(),
            'cpu_usage'        => fake()->randomFloat(1, 0, 100),
            'ram_used'         => fake()->numberBetween(512 * 1024 * 1024, 4 * 1024 * 1024 * 1024),
            'ram_total'        => 8 * 1024 * 1024 * 1024,
            'disk_read_bytes'  => fake()->numberBetween(0, 10 * 1024 * 1024),
            'disk_write_bytes' => fake()->numberBetween(0, 10 * 1024 * 1024),
            'net_rx_bytes'     => fake()->numberBetween(0, 10 * 1024 * 1024),
            'net_tx_bytes'     => fake()->numberBetween(0, 10 * 1024 * 1024),
            'load_avg_1'       => fake()->randomFloat(2, 0, 4),
            'load_avg_5'       => fake()->randomFloat(2, 0, 4),
            'load_avg_15'      => fake()->randomFloat(2, 0, 4),
        ];
    }
}
