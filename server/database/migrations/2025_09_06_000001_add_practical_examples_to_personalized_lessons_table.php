<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personalized_lessons', function (Blueprint $table) {
            $table->json('practical_examples')->nullable()->after('personalized_content');
        });
    }

    public function down(): void
    {
        Schema::table('personalized_lessons', function (Blueprint $table) {
            $table->dropColumn('practical_examples');
        });
    }
};
