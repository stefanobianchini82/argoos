<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite does not support MODIFY COLUMN or ENUM — no-op on SQLite (used in tests).
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE alert_rules MODIFY COLUMN channel ENUM('email','telegram','webhook','slack') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE alert_rules MODIFY COLUMN channel ENUM('email','telegram','webhook') NOT NULL");
        }
    }
};
