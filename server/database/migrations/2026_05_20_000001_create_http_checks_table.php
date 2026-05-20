<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('http_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained()->cascadeOnDelete();
            $table->string('label', 100);
            $table->string('url', 2048);
            $table->string('method', 10)->default('GET');
            $table->unsignedSmallInteger('timeout_seconds')->default(10);
            $table->unsignedSmallInteger('expected_status_code')->default(200);
            $table->string('keyword_match', 255)->nullable();
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
        Schema::dropIfExists('http_checks');
    }
};
