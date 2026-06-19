<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('container_metrics', function (Blueprint $table) {
            $table->id();
            // No FK constraint: MySQL RANGE-partitioned tables do not support foreign keys.
            $table->unsignedBigInteger('host_id');
            $table->string('container_id', 64);
            $table->string('container_name', 255);
            $table->string('image', 255)->nullable();
            $table->float('cpu_percent')->nullable();
            $table->unsignedBigInteger('memory_usage')->nullable();
            $table->unsignedBigInteger('memory_limit')->nullable();
            $table->timestamp('collected_at');

            $table->index(['host_id', 'collected_at'], 'idx_cm_host_collected');
            // Speeds up per-container historical aggregation grouped by name.
            $table->index(['host_id', 'container_name', 'collected_at'], 'idx_cm_host_name_collected');
        });

        // MySQL-specific partitioning — skipped on SQLite (used in tests).
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE container_metrics DROP PRIMARY KEY, ADD PRIMARY KEY (id, collected_at)');
            DB::statement("
                ALTER TABLE container_metrics
                PARTITION BY RANGE (UNIX_TIMESTAMP(collected_at)) (
                    PARTITION p_initial VALUES LESS THAN MAXVALUE
                )
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('container_metrics');
    }
};
