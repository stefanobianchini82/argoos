<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['metrics', 'disk_partitions'] as $table) {
            $existing = collect(DB::select("
                SELECT PARTITION_NAME
                FROM information_schema.PARTITIONS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = ?
                ORDER BY PARTITION_ORDINAL_POSITION
            ", [$table]))->pluck('PARTITION_NAME')->all();

            if (! in_array('p_initial', $existing)) {
                continue;
            }

            // New partitions must have bounds strictly greater than all existing named partitions.
            // This handles the case where a previous job already created some monthly partitions.
            $maxBound = (int) DB::selectOne("
                SELECT COALESCE(MAX(CAST(PARTITION_DESCRIPTION AS UNSIGNED)), 0) AS val
                FROM information_schema.PARTITIONS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = ?
                  AND PARTITION_NAME != 'p_initial'
            ", [$table])?->val;

            $defs = [];
            // From 12 months ago to next month — covers all existing data at deploy time.
            for ($offset = 12; $offset >= -1; $offset--) {
                $start = now()->subMonths($offset)->startOfMonth();
                $end   = $start->copy()->addMonth();
                $name  = 'p_'.$start->format('Y_m');
                $ts    = $end->startOfDay()->timestamp;

                if (! in_array($name, $existing) && $ts > $maxBound) {
                    $defs[] = "PARTITION {$name} VALUES LESS THAN ({$ts})";
                }
            }
            $defs[] = 'PARTITION p_initial VALUES LESS THAN MAXVALUE';

            if (count($defs) === 1) {
                continue; // nothing to add
            }

            DB::statement(
                "ALTER TABLE `{$table}` REORGANIZE PARTITION p_initial INTO ("
                .implode(', ', $defs).')'
            );
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // Collapse all monthly partitions back into p_initial.
        foreach (['metrics', 'disk_partitions'] as $table) {
            $partitions = DB::select("
                SELECT PARTITION_NAME
                FROM information_schema.PARTITIONS
                WHERE TABLE_SCHEMA = DATABASE()
                  AND TABLE_NAME   = ?
                ORDER BY PARTITION_ORDINAL_POSITION
            ", [$table]);

            $names = collect($partitions)->pluck('PARTITION_NAME')->all();

            if (count($names) <= 1) {
                continue;
            }

            DB::statement(
                "ALTER TABLE `{$table}` REORGANIZE PARTITION "
                .implode(', ', $names)
                .' INTO (PARTITION p_initial VALUES LESS THAN MAXVALUE)'
            );
        }
    }
};
