<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Subject;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\User;
use App\Models\StudentGradeEnrollment;
use Illuminate\Database\Seeder;

class RagChatbotSeeder extends Seeder
{
    public function run(): void
    {
        // Create sample grade
        $grade = Grade::create([
            'name' => 'Mathematics Grade 10',
            'description' => 'Advanced mathematics for grade 10 students'
        ]);

        // Create instructor
        $instructor = User::factory()->create([
            'name' => 'Dr. Sarah Johnson',
            'email' => 'sarah.johnson@example.com',
            'role' => 'Instructor',
            'bio' => 'Mathematics professor with 15 years of teaching experience'
        ]);

        // Create student
        $student = User::factory()->create([
            'name' => 'Alex Chen',
            'email' => 'alex.chen@example.com',
            'role' => 'Student',
            'hobbies' => 'playing chess, coding, reading sci-fi',
            'preferences' => 'visual learning with practical examples',
            'bio' => 'High school student interested in mathematics and programming'
        ]);

        // Create subject
        $subject = Subject::create([
            'grade_id' => $grade->id,
            'instructor_id' => $instructor->id,
            'title' => 'Advanced Algebra',
            'description' => 'Comprehensive algebra course covering equations, functions, and graphing'
        ]);

        // Create chapters
        $chapter1 = Chapter::create([
            'subject_id' => $subject->id,
            'title' => 'Linear Equations',
            'order' => 1
        ]);

        $chapter2 = Chapter::create([
            'subject_id' => $subject->id,
            'title' => 'Quadratic Functions',
            'order' => 2
        ]);

        // Create lessons
        $lesson1 = Lesson::create([
            'chapter_id' => $chapter1->id,
            'instructor_id' => $instructor->id,
            'title' => 'Solving Linear Equations',
            'content' => 'Linear equations are mathematical statements that show the equality between two expressions. A linear equation in one variable can be written in the form ax + b = c, where a, b, and c are constants, and x is the variable.

The goal is to find the value of x that makes the equation true. This process is called solving the equation.

Steps to solve a linear equation:
1. Simplify both sides by removing parentheses and combining like terms
2. Use inverse operations to isolate the variable
3. Check your solution by substituting back into the original equation

For example, solve: 2x + 3 = 11
Solution: 2x = 8, x = 4

Linear equations have many real-world applications, including calculating distances, temperatures, and financial planning.',
            'order' => 1,
            'version' => 1
        ]);

        $lesson2 = Lesson::create([
            'chapter_id' => $chapter2->id,
            'instructor_id' => $instructor->id,
            'title' => 'Quadratic Formula and Applications',
            'content' => 'The quadratic formula is a powerful tool for solving quadratic equations of the form ax² + bx + c = 0. The formula is:

x = [-b ± √(b² - 4ac)] / (2a)

Where:
- a is the coefficient of x²
- b is the coefficient of x
- c is the constant term

The discriminant (b² - 4ac) determines the nature of the solutions:
- If discriminant > 0: two distinct real solutions
- If discriminant = 0: one repeated real solution
- If discriminant < 0: two complex solutions

Example: Solve x² + 5x + 6 = 0
Using quadratic formula: x = [-5 ± √(25-24)] / 2 = [-5 ± 1]/2
Solutions: x = -2, x = -3

Quadratic equations model projectile motion, optimization problems, and many physics phenomena.',
            'order' => 1,
            'version' => 1
        ]);

        // Enroll student in grade
        StudentGradeEnrollment::create([
            'user_id' => $student->id,
            'grade_id' => $grade->id,
            'status' => 'accepted',
            'invited_by' => $instructor->id,
            'invited_at' => now(),
            'accepted_at' => now()
        ]);

        $this->command->info('Sample data created successfully!');
        $this->command->info("Grade: {$grade->name}");
        $this->command->info("Instructor: {$instructor->name}");
        $this->command->info("Student: {$student->name}");
        $this->command->info("Subject: {$subject->title}");
        $this->command->info("Lessons created: 2");
    }
}
