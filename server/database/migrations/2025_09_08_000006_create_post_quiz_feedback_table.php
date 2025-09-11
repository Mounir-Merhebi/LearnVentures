<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_quiz_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_quiz_id')->constrained('student_quizzes')->onDelete('cascade');
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->longText('overall_performance');
            $table->json('weak_areas');
            $table->json('recommendations');
            $table->json('study_plan');
            $table->json('recommended_lesson_ids');
            $table->timestamp('analyzed_at');
            $table->timestamps();

            // Unique constraint: one report per attempt
            $table->unique('student_quiz_id');

            // Indexes
            $table->index('student_quiz_id');
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_quiz_feedback');
    }
};
