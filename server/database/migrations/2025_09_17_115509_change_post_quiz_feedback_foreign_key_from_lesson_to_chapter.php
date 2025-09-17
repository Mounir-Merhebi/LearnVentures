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
        Schema::table('post_quiz_feedback', function (Blueprint $table) {
            // Add foreign key constraint to chapter_id (column already exists)
            $table->foreign('chapter_id')->references('id')->on('chapters')->onDelete('cascade');
            $table->index('chapter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_quiz_feedback', function (Blueprint $table) {
            // Drop foreign key constraint on chapter_id
            $table->dropForeign(['chapter_id']);
            $table->dropIndex(['chapter_id']);
        });
    }
};
