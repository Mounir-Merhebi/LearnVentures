<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('personalized_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('original_lesson_title');
            $table->text('original_lesson_content');
            $table->string('personalized_title');
            $table->text('personalized_content');
            $table->text('learning_approach');
            $table->json('practical_examples'); // Array of examples
            $table->json('next_steps'); // Array of next steps
            $table->timestamp('generated_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personalized_lessons');
    }
};
