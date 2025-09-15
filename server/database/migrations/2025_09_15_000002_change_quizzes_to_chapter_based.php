<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the existing foreign key constraint
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
            $table->dropIndex(['lesson_id']);
        });

        // Rename lesson_id to chapter_id
        Schema::table('quizzes', function (Blueprint $table) {
            $table->renameColumn('lesson_id', 'chapter_id');
        });

        // Add new foreign key constraint to chapters
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreign('chapter_id')->references('id')->on('chapters')->onDelete('cascade');
            $table->index('chapter_id');
        });
    }

    public function down(): void
    {
        // Reverse the changes
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
            $table->dropIndex(['chapter_id']);
        });

        // Rename back to lesson_id
        Schema::table('quizzes', function (Blueprint $table) {
            $table->renameColumn('chapter_id', 'lesson_id');
        });

        // Add back the original foreign key constraint
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreign('lesson_id')->references('id')->on('lessons')->onDelete('cascade');
            $table->index('lesson_id');
        });
    }
};
