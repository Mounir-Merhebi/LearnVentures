<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('lessons')->onDelete('cascade');
            $table->string('title');
            $table->integer('question_count')->nullable();
            $table->integer('time_limit_seconds')->nullable();
            $table->timestamps();

            // Index for lesson_id
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quizzes');
    }
};
