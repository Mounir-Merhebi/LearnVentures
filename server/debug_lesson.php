<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Chapter;
use App\Models\Subject;

echo "Debugging lesson creation...\n";

// Get a chapter
$chapter = Chapter::with('subject')->first();
if (!$chapter) {
    echo "No chapters found!\n";
    exit(1);
}

echo "Chapter ID: {$chapter->id}\n";
echo "Chapter subject_id: {$chapter->subject_id}\n";

if ($chapter->subject) {
    echo "Subject found!\n";
    echo "Subject ID: {$chapter->subject->id}\n";
    echo "Subject instructor_id: {$chapter->subject->instructor_id}\n";
} else {
    echo "Subject not found for chapter!\n";

    // Try to find subject manually
    $subject = Subject::find($chapter->subject_id);
    if ($subject) {
        echo "Subject found manually - ID: {$subject->id}, instructor_id: {$subject->instructor_id}\n";
    } else {
        echo "Subject not found even manually!\n";
    }
}

// Test the exact code from the controller
echo "\nTesting controller logic...\n";
try {
    $chapter2 = Chapter::with('subject')->find($chapter->id);
    if (!$chapter2 || !$chapter2->subject) {
        echo "Chapter or subject not found in controller logic\n";
    } else {
        echo "Controller logic would set instructor_id: {$chapter2->subject->instructor_id}\n";
    }
} catch (\Exception $e) {
    echo "Exception in controller logic: " . $e->getMessage() . "\n";
}

