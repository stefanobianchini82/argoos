<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            // No FK constraint: MySQL RANGE-partitioned tables do not support foreign keys.
            // Referential integrity is enforced by AuthenticateAgent middleware.
            $table->unsignedBigInteger('host_id');
            $table->timestamp('collected_at');
            $table->float('cpu_usage')->nullable();
            $table->unsignedBigInteger('ram_used')->nullable();
            $table->unsignedBigInteger('ram_total')->nullable();
            $table->unsignedBigInteger('disk_read_bytes')->nullable();
            $table->unsignedBigInteger('disk_write_bytes')->nullable();
            $table->unsignedBigInteger('net_rx_bytes')->nullable();
            $table->unsignedBigInteger('net_tx_bytes')->nullable();
            $table->float('load_avg_1')->nullable();
            $table->float('load_avg_5')->nullable();
            $table->float('load_avg_15')->nullable();

            $table->index(['host_id', 'collected_at'], 'idx_host_collected');
        });

        // Add RANGE partitioning by month. p_initial catches all data until
        // the cleanup job introduces real monthly partitions.
        DB::statement("
            ALTER TABLE metrics
            PARTITION BY RANGE (UNIX_TIMESTAMP(collected_at)) (
                PARTITION p_initial VALUES LESS THAN MAXVALUE
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('metrics');
    }
};
