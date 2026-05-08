<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->timestamp('last_offline_notified_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('hosts', function (Blueprint $table) {
            $table->dropColumn('last_offline_notified_at');
        });
    }
};
