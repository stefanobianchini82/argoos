<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->unsignedBigInteger('uptime_seconds')->nullable()->after('load_avg_15');
        });
    }

    public function down(): void
    {
        Schema::table('metrics', function (Blueprint $table) {
            $table->dropColumn('uptime_seconds');
        });
    }
};
