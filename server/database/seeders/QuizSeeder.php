<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\StudentQuiz;
use App\Models\StudentQuizAnswer;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing data
        $lesson = Lesson::first();
        $student = User::where('role', 'Student')->first();

        if (!$lesson || !$student) {
            $this->command->error('No lessons or students found. Run RagChatbotSeeder first.');
            return;
        }

        // Create a sample quiz
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => 'Mathematics Fundamentals Quiz',
            'question_count' => 5,
            'time_limit_seconds' => 600, // 10 minutes
        ]);

        // Create quiz questions
        $questions = [
            [
                'body' => 'What is the result of 2 + 3?',
                'options' => ['3', '4', '5', '6'],
                'correct_option' => '5',
                'concept_slug' => 'basic_arithmetic'
            ],
            [
                'body' => 'Solve for x: 2x + 3 = 11',
                'options' => ['x = 3', 'x = 4', 'x = 5', 'x = 6'],
                'correct_option' => 'x = 4',
                'concept_slug' => 'linear_equations'
            ],
            [
                'body' => 'What is the quadratic formula?',
                'options' => ['x = -b/a', 'x = (-b ± √(b²-4ac))/2a', 'x = b²/2a', 'x = a/b'],
                'correct_option' => 'x = (-b ± √(b²-4ac))/2a',
                'concept_slug' => 'quadratic_equations'
            ],
            [
                'body' => 'What is the area of a rectangle with length 5 and width 3?',
                'options' => ['8', '15', '16', '20'],
                'correct_option' => '15',
                'concept_slug' => 'area_calculation'
            ],
            [
                'body' => 'Simplify: 2x + 3x - x',
                'options' => ['4x', '6x', '2x', '0'],
                'correct_option' => '4x',
                'concept_slug' => 'algebraic_simplification'
            ],
        ];

        foreach ($questions as $index => $questionData) {
            QuizQuestion::create([
                'quiz_id' => $quiz->id,
                'body' => $questionData['body'],
                'options_json' => json_encode($questionData['options']),
                'correct_option' => $questionData['correct_option'],
                'order' => $index + 1,
                'concept_slug' => $questionData['concept_slug'],
            ]);
        }

        // Create a completed quiz attempt for the student
        $studentQuiz = StudentQuiz::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => 80.0, // 4 out of 5 correct
            'started_at' => now()->subMinutes(15),
            'completed_at' => now()->subMinutes(5),
            'duration_seconds' => 600, // 10 minutes
        ]);

        // Create answers for the quiz attempt
        $quizQuestions = QuizQuestion::where('quiz_id', $quiz->id)->orderBy('order')->get();
        $studentAnswers = [
            ['selected' => '5', 'is_correct' => true],   // Question 1 - correct
            ['selected' => 'x = 4', 'is_correct' => true], // Question 2 - correct
            ['selected' => 'x = -b/a', 'is_correct' => false], // Question 3 - wrong
            ['selected' => '15', 'is_correct' => true], // Question 4 - correct
            ['selected' => '4x', 'is_correct' => true], // Question 5 - correct
        ];

        foreach ($quizQuestions as $index => $question) {
            StudentQuizAnswer::create([
                'student_quiz_id' => $studentQuiz->id,
                'question_id' => $question->id,
                'selected_answer' => $studentAnswers[$index]['selected'],
                'is_correct' => $studentAnswers[$index]['is_correct'],
                'correct_option_snapshot' => $question->correct_option,
            ]);
        }

        $this->command->info('Quiz data created successfully!');
        $this->command->info("Quiz: {$quiz->title}");
        $this->command->info("Questions: {$quizQuestions->count()}");
        $this->command->info("Student Quiz ID: {$studentQuiz->id}");
        $this->command->info("Score: {$studentQuiz->score}%");
        $this->command->info("Student: {$student->name}");
        $this->command->info("");
        $this->command->info("📝 Use this Student Quiz ID for testing:");
        $this->command->info("{$studentQuiz->id}");
    }
}
