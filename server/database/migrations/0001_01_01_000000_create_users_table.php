<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
    
            $table->string('email')->unique();
            $table->string('password');
    
            $table->enum('role', ['Student','Instructor','Moderator','Admin'])->index();
    
            $table->string('name');
            $table->text('hobbies')->nullable();
            $table->text('preferences')->nullable();
            $table->text('bio')->nullable();
            $table->string('excel_sheet_path')->nullable();
    
            // created_at only (match your model)
            $table->timestamp('created_at')->useCurrent();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
    
};
