<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_previews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_filename');
            $table->string('file_type', 10)->index();
            $table->string('disk', 20)->default('local');
            $table->string('file_path')->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->json('result')->nullable();
            $table->json('warnings')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_previews');
    }
};
