<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE alert_rules MODIFY COLUMN channel ENUM('email','telegram','webhook','slack') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE alert_rules MODIFY COLUMN channel ENUM('email','telegram','webhook') NOT NULL");
    }
};
