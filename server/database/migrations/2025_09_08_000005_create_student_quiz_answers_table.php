<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_quiz_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_quiz_id')->constrained('student_quizzes')->onDelete('cascade');
            $table->foreignId('question_id')->constrained('quiz_questions')->onDelete('cascade');
            $table->string('selected_answer');
            $table->boolean('is_correct');
            $table->string('correct_option_snapshot')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('student_quiz_id');
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_quiz_answers');
    }
};
