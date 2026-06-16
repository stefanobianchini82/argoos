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

        $cutoff    = now()->subDays(7);
        $nextMonth = now()->addMonth()->startOfMonth();

        foreach (['metrics', 'disk_partitions'] as $table) {
            $this->addNextMonthPartition($table, $nextMonth);
            $this->dropPartitionsOlderThan($table, $cutoff);
            DB::table($table)->where('collected_at', '<', $cutoff)->delete();
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

    private function dropPartitionsOlderThan(string $table, Carbon $cutoff): void
    {
        $rows = DB::select("
            SELECT PARTITION_NAME
            FROM information_schema.PARTITIONS
            WHERE TABLE_SCHEMA          = DATABASE()
              AND TABLE_NAME            = ?
              AND PARTITION_NAME       != 'p_initial'
              AND PARTITION_DESCRIPTION != 'MAXVALUE'
              AND CAST(PARTITION_DESCRIPTION AS UNSIGNED) <= ?
        ", [$table, $cutoff->timestamp]);

        foreach ($rows as $row) {
            DB::statement("ALTER TABLE `{$table}` DROP PARTITION `{$row->PARTITION_NAME}`");
        }
    }
}
