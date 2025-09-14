<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_chat_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->date('report_date');
            $table->string('tldr', 500)->nullable();
            $table->json('key_topics')->nullable();
            $table->json('misconceptions')->nullable();
            $table->json('next_actions')->nullable();
            $table->json('stats')->nullable();
            $table->json('full_summary')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'report_date'], 'uq_daily_reports_student_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_chat_reports');
    }
};

