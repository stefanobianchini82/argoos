<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->string('metric', 50);
            $table->enum('operator', ['>', '<', '>=', '<=']);
            $table->float('threshold');
            $table->unsignedInteger('duration_minutes')->default(5);
            $table->enum('channel', ['email', 'telegram', 'webhook', 'slack']);
            $table->string('channel_target', 255);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();

            $table->index('host_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
