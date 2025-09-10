<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chunk_id')->constrained('kb_chunks')->onDelete('cascade');
            $table->string('model_name');
            $table->integer('dim');
            $table->longText('vector'); // Store as JSON string for vectors
            $table->timestamps();

            // Add unique constraint on chunk_id and model_name
            $table->unique(['chunk_id', 'model_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_embeddings');
    }
};
