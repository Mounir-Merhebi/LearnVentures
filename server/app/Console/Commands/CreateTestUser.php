<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\StudentGradeEnrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTestUser extends Command
{
    protected $signature = 'create:test-user';
    protected $description = 'Create a test user for API testing';

    public function handle()
    {
        $this->info('Creating test user...');

        // Check if test user already exists
        $existingUser = User::where('email', 'test@example.com')->first();
        if ($existingUser) {
            $this->warn('Test user already exists!');
            $this->line('Email: test@example.com');
            $this->line('Password: password');

            // Check enrollment
            $enrollment = StudentGradeEnrollment::where('user_id', $existingUser->id)->where('grade_id', 2)->first();
            if ($enrollment && $enrollment->status === 'accepted') {
                $this->info('User is properly enrolled in Mathematics Grade 10');
            } else {
                $this->warn('User not enrolled in grade 2, fixing...');
                StudentGradeEnrollment::updateOrCreate(
                    ['user_id' => $existingUser->id, 'grade_id' => 2],
                    [
                        'status' => 'accepted',
                        'invited_by' => $existingUser->id,
                        'accepted_at' => now()
                    ]
                );
                $this->info('Enrollment created/fixed');
            }
            return Command::SUCCESS;
        }

        // Create new user
        $user = User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'Student',
            'name' => 'Test User'
        ]);

        $this->info('✓ User created: test@example.com / password');

        // Enroll in Mathematics Grade 10
        StudentGradeEnrollment::create([
            'user_id' => $user->id,
            'grade_id' => 2,
            'status' => 'accepted',
            'invited_by' => $user->id, // Self-invited for testing
            'accepted_at' => now()
        ]);

        $this->info('✓ Enrolled in Mathematics Grade 10 (grade_id: 2)');

        $this->newLine();
        $this->info('Use these credentials in Postman:');
        $this->line('Email: test@example.com');
        $this->line('Password: password');

        return Command::SUCCESS;
    }
}
