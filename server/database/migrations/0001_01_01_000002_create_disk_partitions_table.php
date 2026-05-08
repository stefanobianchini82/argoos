<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disk_partitions', function (Blueprint $table) {
            $table->id();
            // No FK constraint: MySQL RANGE-partitioned tables do not support foreign keys.
            $table->unsignedBigInteger('host_id');
            $table->string('mount_point', 255);
            $table->unsignedBigInteger('total')->nullable();
            $table->unsignedBigInteger('used')->nullable();
            $table->unsignedBigInteger('free')->nullable();
            $table->timestamp('collected_at');

            $table->index(['host_id', 'collected_at'], 'idx_host_collected');
        });

        DB::statement('ALTER TABLE disk_partitions DROP PRIMARY KEY, ADD PRIMARY KEY (id, collected_at)');
        DB::statement("
            ALTER TABLE disk_partitions
            PARTITION BY RANGE (UNIX_TIMESTAMP(collected_at)) (
                PARTITION p_initial VALUES LESS THAN MAXVALUE
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('disk_partitions');
    }
};
