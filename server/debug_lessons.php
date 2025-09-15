<?php

require_once 'bootstrap/app.php';

echo "=== Debug Lessons ===\n\n";

$lessons = \App\Models\Lesson::with(['chapter.subject'])->get();

echo "Total lessons: " . $lessons->count() . "\n\n";

foreach ($lessons as $lesson) {
    echo "Lesson: {$lesson->title}\n";
    echo "  Chapter: " . ($lesson->chapter->title ?? 'null') . "\n";
    echo "  Subject: " . ($lesson->chapter->subject->title ?? 'null') . "\n";
    echo "  Grade ID: " . ($lesson->chapter->subject->grade_id ?? 'null') . "\n";
    echo "  Grade Name: " . ($lesson->chapter->subject->grade->name ?? 'null') . "\n";
    echo "\n";
}

// Check grades
echo "=== Grades ===\n";
$grades = \App\Models\Grade::all();
foreach ($grades as $grade) {
    echo "Grade ID: {$grade->id} - {$grade->name}\n";
}

echo "\n=== Chat Sessions ===\n";
$sessions = \App\Models\ChatSession::with('grade')->get();
foreach ($sessions as $session) {
    echo "Session ID: {$session->id} - Grade: " . ($session->grade->name ?? 'null') . " (ID: {$session->grade_id})\n";
}
