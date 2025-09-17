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
        Schema::create('daily_chat_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->date('report_date');
            $table->text('tldr')->nullable(); // TL;DR summary of the day's conversations
            $table->json('key_topics')->nullable(); // Key topics discussed
            $table->json('misconceptions')->nullable(); // Misconceptions identified
            $table->json('next_actions')->nullable(); // Recommended next actions
            $table->json('stats')->nullable(); // Usage statistics
            $table->longText('full_summary')->nullable(); // Full detailed summary
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            // Ensure one report per student per day
            $table->unique(['student_id', 'report_date']);

            // Indexes
            $table->index(['student_id', 'report_date']);
            $table->index('report_date');
            $table->index('analyzed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_chat_reports');
    }
};
