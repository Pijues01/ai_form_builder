<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->json('data');
            $table->text('searchable')->nullable();
            $table->string('ip', 45)->nullable()->index();
            $table->string('user_agent', 512)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['form_id', 'created_at']);
            $table->fullText('searchable');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
