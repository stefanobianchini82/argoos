<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alert_events', function (Blueprint $table) {
            $table->json('trigger_context')->nullable()->after('peak_value');
        });
    }

    public function down(): void
    {
        Schema::table('alert_events', function (Blueprint $table) {
            $table->dropColumn('trigger_context');
        });
    }
};
