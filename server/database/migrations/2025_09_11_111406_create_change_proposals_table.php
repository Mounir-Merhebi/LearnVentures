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
        Schema::create('change_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('moderator_id')->constrained('users')->onDelete('cascade');
            $table->json('scope')->nullable(false);
            $table->string('excel_hash', 128)->nullable(false);
            $table->string('excel_path')->nullable();
            $table->json('excel_snapshot')->nullable(false);
            $table->json('db_snapshot')->nullable(false);
            $table->json('diff_json')->nullable(false);
            $table->enum('status', ['pending', 'approved', 'rejected', 'applied', 'failed'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamps();

            // Indexes
            $table->unique('excel_hash');
            $table->index('status');
            $table->index('moderator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('change_proposals');
    }
};
