<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class PruneOldMetrics implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        $nextMonth = now()->addMonth()->startOfMonth();
        $toDrop    = now()->subMonths(2)->startOfMonth();

        foreach (['metrics', 'disk_partitions', 'process_memory'] as $table) {
            $this->addNextMonthPartition($table, $nextMonth);
            $this->dropPartitionIfExists($table, $toDrop);
        }
    }

    private function addNextMonthPartition(string $table, Carbon $month): void
    {
        $name = 'p_'.$month->format('Y_m');

        $exists = DB::selectOne("
            SELECT 1
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA  = DATABASE()
              AND TABLE_NAME    = ?
              AND PARTITION_NAME = ?
        ", [$table, $name]);

        if ($exists) {
            return;
        }

        $ts = $month->copy()->addMonth()->timestamp;

        DB::statement(
            "ALTER TABLE `{$table}` REORGANIZE PARTITION p_initial INTO ("
            ."PARTITION {$name} VALUES LESS THAN ({$ts}), "
            .'PARTITION p_initial VALUES LESS THAN MAXVALUE)'
        );
    }

    private function dropPartitionIfExists(string $table, Carbon $month): void
    {
        $name = 'p_'.$month->format('Y_m');

        $exists = DB::selectOne("
            SELECT 1
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA  = DATABASE()
              AND TABLE_NAME    = ?
              AND PARTITION_NAME = ?
        ", [$table, $name]);

        if ($exists) {
            DB::statement("ALTER TABLE `{$table}` DROP PARTITION `{$name}`");
        }
    }
}
