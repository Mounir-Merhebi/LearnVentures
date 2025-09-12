<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

echo "=== Users in Database ===\n";
$users = \App\Models\User::all();

if ($users->isEmpty()) {
    echo "No users found. Creating a test user...\n";

    $user = \App\Models\User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'role' => 'student'
    ]);

    echo "Created user: {$user->email}\n";

    // Also enroll the user in grade 2 (Mathematics Grade 10)
    \App\Models\StudentGradeEnrollment::create([
        'user_id' => $user->id,
        'grade_id' => 2,
        'status' => 'accepted'
    ]);

    echo "Enrolled user in Mathematics Grade 10\n";
} else {
    foreach ($users as $user) {
        echo "ID: {$user->id}, Email: {$user->email}, Role: {$user->role}\n";
    }
}

echo "\n=== Enrollments ===\n";
$enrollments = \App\Models\StudentGradeEnrollment::with(['user', 'grade'])->get();
foreach ($enrollments as $enrollment) {
    echo "User: {$enrollment->user->email} -> Grade: {$enrollment->grade->name} (Status: {$enrollment->status})\n";
}
