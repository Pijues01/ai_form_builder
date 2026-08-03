<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('mode', ['create', 'edit'])->default('create')->index();
            $table->text('prompt');
            $table->json('input')->nullable();
            $table->json('result')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->text('error')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('tokens_prompt')->nullable();
            $table->unsignedInteger('tokens_completion')->nullable();
            $table->unsignedInteger('tokens_total')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedTinyInteger('repair_attempts')->default(0);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_generations');
    }
};
