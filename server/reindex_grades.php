<?php

require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\Artisan;

echo "Starting grade reindexing...\n";

// Get all grades
$grades = \App\Models\Grade::all();

foreach ($grades as $grade) {
    echo "Reindexing grade: {$grade->name} (ID: {$grade->id})\n";

    try {
        Artisan::call('kb:reindex-grade', ['gradeId' => $grade->id]);
        echo "✓ Completed reindexing for grade {$grade->id}\n";
    } catch (Exception $e) {
        echo "✗ Error reindexing grade {$grade->id}: {$e->getMessage()}\n";
    }
}

echo "Grade reindexing completed!\n";
