<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_quizzes', function (Blueprint $table) {
            // Drop the unique constraint on user_id and quiz_id to allow multiple attempts
            $table->dropUnique(['user_id', 'quiz_id']);
        });
    }

    public function down(): void
    {
        Schema::table('student_quizzes', function (Blueprint $table) {
            // Restore the unique constraint
            $table->unique(['user_id', 'quiz_id']);
        });
    }
};
