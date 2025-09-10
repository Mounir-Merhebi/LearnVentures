<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->integer('chunk_index');
            $table->longText('text');
            $table->integer('source_lesson_version')->nullable();
            $table->string('content_hash')->nullable();
            $table->timestamps();

            // Add index for performance
            $table->index(['lesson_id', 'chunk_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_chunks');
    }
};
