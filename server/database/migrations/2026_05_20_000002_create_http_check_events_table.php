<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('http_check_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('http_check_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_up');
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->unsignedInteger('response_ms')->nullable();
            $table->timestamp('triggered_at');
            $table->timestamp('resolved_at')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index('http_check_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('http_check_events');
    }
};
