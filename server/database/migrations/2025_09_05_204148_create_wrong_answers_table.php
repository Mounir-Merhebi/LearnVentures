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
        Schema::create('wrong_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('lesson_topic');
            $table->text('question');
            $table->text('user_answer');
            $table->text('correct_answer');
            $table->boolean('analyzed')->default(false); // Whether this has been sent to AI for analysis
            $table->foreignId('performance_analysis_id')->nullable()->constrained('performance_analyses')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wrong_answers');
    }
};
