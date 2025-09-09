<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('email')->unique();
            $table->string('password');

            $table->enum('role', ['Student','Instructor','Moderator','Admin'])->default('Student')->index();

            $table->string('name');
            $table->string('hobbies')->nullable();
            $table->string('preferences')->nullable();
            $table->string('bio')->nullable();

            $table->timestamps(); // This creates both created_at and updated_at
        });
    }
    
    
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
    
};
