<?php

namespace Database\Seeders;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\StudentQuiz;
use App\Models\StudentQuizAnswer;
use App\Models\User;
use App\Models\Lesson;
use App\Models\Chapter;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing data
        $student = User::where('role', 'Student')->first();

        if (!$student) {
            $this->command->error('No students found. Run RagChatbotSeeder first.');
            return;
        }

        // Get algebra subject and its chapters
        $algebraSubject = Subject::where('title', 'Advanced Algebra')->first();

        if (!$algebraSubject) {
            $this->command->error('Algebra subject not found. Run RagChatbotSeeder first.');
            return;
        }

        $chapter1 = Chapter::where('subject_id', $algebraSubject->id)->where('order', 1)->first();
        $chapter2 = Chapter::where('subject_id', $algebraSubject->id)->where('order', 2)->first();

        if (!$chapter1 || !$chapter2) {
            $this->command->error('Chapters not found. Run RagChatbotSeeder first.');
            return;
        }

        $lesson1 = Lesson::where('chapter_id', $chapter1->id)->first();
        $lesson2 = Lesson::where('chapter_id', $chapter2->id)->first();

        if (!$lesson1 || !$lesson2) {
            $this->command->error('Lessons not found. Run RagChatbotSeeder first.');
            return;
        }

        // Create Chapter 1 Quiz: Linear Equations
        $this->createAlgebraQuiz(
            $lesson1,
            'Chapter 1: Linear Equations Quiz',
            $this->getChapter1Questions(),
            $student
        );

        // Create Chapter 2 Quiz: Quadratic Functions
        $this->createAlgebraQuiz(
            $lesson2,
            'Chapter 2: Quadratic Functions Quiz',
            $this->getChapter2Questions(),
            $student
        );

        $this->command->info('Algebra quiz data created successfully!');
    }

    private function createAlgebraQuiz($lesson, $title, $questions, $student)
    {
        // Create quiz
        $quiz = Quiz::create([
            'lesson_id' => $lesson->id,
            'title' => $title,
            'question_count' => count($questions),
            'time_limit_seconds' => 900, // 15 minutes
        ]);

        // Create quiz questions
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

        // Create a sample completed quiz attempt
        $quizQuestions = QuizQuestion::where('quiz_id', $quiz->id)->orderBy('order')->get();
        $correctAnswers = count($questions) - 2; // Simulate getting 2 wrong
        $score = round(($correctAnswers / count($questions)) * 100, 2);

        $studentQuiz = StudentQuiz::create([
            'user_id' => $student->id,
            'quiz_id' => $quiz->id,
            'score' => $score,
            'started_at' => now()->subMinutes(20),
            'completed_at' => now()->subMinutes(5),
            'duration_seconds' => 900,
        ]);

        // Create sample answers
        foreach ($quizQuestions as $index => $question) {
            $isCorrect = $index < $correctAnswers; // First N answers are correct
            $selectedAnswer = $isCorrect
                ? $questions[$index]['correct_option']
                : $questions[$index]['options'][0]; // Wrong answer for demo

            StudentQuizAnswer::create([
                'student_quiz_id' => $studentQuiz->id,
                'question_id' => $question->id,
                'selected_answer' => $selectedAnswer,
                'is_correct' => $isCorrect,
                'correct_option_snapshot' => $question->correct_option,
            ]);
        }

        $this->command->info("✅ Created: {$title}");
        $this->command->info("   Questions: " . count($questions));
        $this->command->info("   Quiz ID: {$quiz->id}");
        $this->command->info("   Sample Score: {$score}%");
        $this->command->info("");
    }

    private function getChapter1Questions()
    {
        return [
            [
                'body' => 'Solve for x: 2x + 5 = 17',
                'options' => ['x = 6', 'x = 7', 'x = 8', 'x = 11'],
                'correct_option' => 'x = 6',
                'concept_slug' => 'solving_linear_equations'
            ],
            [
                'body' => 'What is the slope of the line y = 3x + 2?',
                'options' => ['2', '3', '1', '0'],
                'correct_option' => '3',
                'concept_slug' => 'slope_of_line'
            ],
            [
                'body' => 'Solve the system: 2x + y = 8 and x - y = 2',
                'options' => ['x = 2, y = 4', 'x = 3, y = 2', 'x = 1, y = 6', 'x = 4, y = 0'],
                'correct_option' => 'x = 2, y = 4',
                'concept_slug' => 'system_of_equations'
            ],
            [
                'body' => 'Which of the following is a linear equation?',
                'options' => ['y = x² + 2', 'y = 2x + 1', 'y = x³', 'y = 1/x'],
                'correct_option' => 'y = 2x + 1',
                'concept_slug' => 'linear_vs_nonlinear'
            ],
            [
                'body' => 'Find the y-intercept of 3x - 2y = 6',
                'options' => ['-3', '3', '2', '-2'],
                'correct_option' => '-3',
                'concept_slug' => 'y_intercept'
            ],
            [
                'body' => 'Simplify: 2(3x + 4) - x = ?',
                'options' => ['5x + 4', '5x + 8', '7x + 4', '6x + 8'],
                'correct_option' => '5x + 8',
                'concept_slug' => 'distributive_property'
            ],
            [
                'body' => 'What is the solution to |x - 3| = 5?',
                'options' => ['x = 8 only', 'x = -2 only', 'x = 8 or x = -2', 'x = 2 or x = 6'],
                'correct_option' => 'x = 8 or x = -2',
                'concept_slug' => 'absolute_value_equations'
            ],
            [
                'body' => 'Which line has a steeper slope: y = 4x + 1 or y = 2x - 3?',
                'options' => ['y = 4x + 1', 'y = 2x - 3', 'They have equal slopes', 'Cannot determine'],
                'correct_option' => 'y = 4x + 1',
                'concept_slug' => 'comparing_slopes'
            ]
        ];
    }

    private function getChapter2Questions()
    {
        return [
            [
                'body' => 'What is the quadratic formula?',
                'options' => ['x = -b/a', 'x = (-b ± √(b²-4ac))/2a', 'x = b²/2a', 'x = a/b'],
                'correct_option' => 'x = (-b ± √(b²-4ac))/2a',
                'concept_slug' => 'quadratic_formula'
            ],
            [
                'body' => 'Solve x² - 5x + 6 = 0',
                'options' => ['x = 2, x = 3', 'x = 1, x = 6', 'x = -2, x = -3', 'x = 5, x = 1'],
                'correct_option' => 'x = 2, x = 3',
                'concept_slug' => 'factoring_quadratics'
            ],
            [
                'body' => 'What does the discriminant tell us about the roots?',
                'options' => ['The sum of roots', 'Nature of roots', 'Product of roots', 'Both roots'],
                'correct_option' => 'Nature of roots',
                'concept_slug' => 'discriminant'
            ],
            [
                'body' => 'Find the vertex of y = x² - 4x + 3',
                'options' => ['(2, -1)', '(2, 3)', '(-2, 3)', '(4, 3)'],
                'correct_option' => '(2, -1)',
                'concept_slug' => 'vertex_of_parabola'
            ],
            [
                'body' => 'What is the axis of symmetry for y = 2x² - 8x + 6?',
                'options' => ['x = 2', 'x = 4', 'x = -2', 'x = 8'],
                'correct_option' => 'x = 2',
                'concept_slug' => 'axis_of_symmetry'
            ],
            [
                'body' => 'Solve 2x² + 3x - 2 = 0 using quadratic formula',
                'options' => ['x = 1/2, x = -2', 'x = 1, x = -1', 'x = 2, x = -1/2', 'x = -2, x = 1/2'],
                'correct_option' => 'x = 1/2, x = -2',
                'concept_slug' => 'quadratic_formula_application'
            ],
            [
                'body' => 'Which quadratic function opens upward?',
                'options' => ['y = -x² + 2', 'y = 2x² - 1', 'y = -3x² + 4', 'y = -x²'],
                'correct_option' => 'y = 2x² - 1',
                'concept_slug' => 'direction_of_parabola'
            ],
            [
                'body' => 'What is the maximum value of y = -x² + 4x - 3?',
                'options' => ['1', '2', '3', '4'],
                'correct_option' => '1',
                'concept_slug' => 'maximum_minimum_values'
            ]
        ];
    }
}
