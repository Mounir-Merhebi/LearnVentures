<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_grade_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('grade_id')->constrained('grades')->onDelete('cascade');
            $table->enum('status', ['invited', 'accepted', 'revoked'])->default('invited');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            // Add unique constraint on user_id and grade_id
            $table->unique(['user_id', 'grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_grade_enrollments');
    }
};
