<?php

namespace Database\Factories;

use App\Models\Host;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiskPartitionFactory extends Factory
{
    public function definition(): array
    {
        $total = 100 * 1024 * 1024 * 1024;
        $used  = fake()->numberBetween(1 * 1024 * 1024 * 1024, $total);

        return [
            'host_id'      => Host::factory(),
            'mount_point'  => '/',
            'total'        => $total,
            'used'         => $used,
            'free'         => $total - $used,
            'collected_at' => now(),
        ];
    }
}
