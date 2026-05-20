<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_memory', function (Blueprint $table) {
            $table->id();
            // No FK constraint: MySQL RANGE-partitioned tables do not support foreign keys.
            $table->unsignedBigInteger('host_id');
            $table->unsignedInteger('pid');
            $table->string('name', 255);
            $table->unsignedBigInteger('mem_rss');
            $table->timestamp('collected_at');

            $table->index(['host_id', 'collected_at'], 'idx_pm_host_collected');
        });

        // MySQL-specific partitioning — skipped on SQLite (used in tests).
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE process_memory DROP PRIMARY KEY, ADD PRIMARY KEY (id, collected_at)');
            DB::statement("
                ALTER TABLE process_memory
                PARTITION BY RANGE (UNIX_TIMESTAMP(collected_at)) (
                    PARTITION p_initial VALUES LESS THAN MAXVALUE
                )
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('process_memory');
    }
};
