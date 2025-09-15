<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\StudentQuiz;
use App\Models\StudentQuizAnswer;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuizController extends Controller
{
    /**
     * Get quiz by chapter ID
     */
    public function getByChapter($chapterId): JsonResponse
    {
        try {
            $quiz = Quiz::where('chapter_id', $chapterId)
                ->with(['questions' => function($query) {
                    $query->orderBy('order');
                }])
                ->first();

            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'No quiz found for this chapter'
                ], 404);
            }

            // Get user's previous attempt if exists
            $userId = Auth::id();
            $previousAttempt = null;

            if ($userId) {
                $previousAttempt = StudentQuiz::where('user_id', $userId)
                    ->where('quiz_id', $quiz->id)
                    ->with('answers')
                    ->latest()
                    ->first();
            }

            // Format questions for frontend
            $questions = $quiz->questions->map(function($question) {
                return [
                    'id' => $question->id,
                    'question' => $question->body,
                    'options' => json_decode($question->options_json),
                    'correctAnswer' => $question->correct_option, // Only for review mode
                    'order' => $question->order
                ];
            });

            return response()->json([
                'success' => true,
                'quiz' => [
                    'id' => $quiz->id,
                    'title' => $quiz->title,
                    'totalQuestions' => $quiz->question_count,
                    'timeLimit' => $quiz->time_limit_seconds,
                    'questions' => $questions,
                    'previousAttempt' => $previousAttempt ? [
                        'id' => $previousAttempt->id,
                        'score' => $previousAttempt->score,
                        'startedAt' => $previousAttempt->started_at,
                        'completedAt' => $previousAttempt->completed_at,
                        'duration' => $previousAttempt->duration_seconds,
                        'answers' => $previousAttempt->answers->map(function($answer) {
                            return [
                                'questionId' => $answer->question_id,
                                'selectedAnswer' => $answer->selected_answer,
                                'isCorrect' => $answer->is_correct,
                                'correctOption' => $answer->correct_option_snapshot
                            ];
                        })
                    ] : null
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Start a quiz attempt
     */
    public function startQuiz(Request $request, $quizId): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Check if quiz exists
            $quiz = Quiz::find($quizId);
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], 404);
            }

            // Check if user already has an active attempt
            $activeAttempt = StudentQuiz::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->whereNull('completed_at')
                ->first();

            if ($activeAttempt) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have an active quiz attempt',
                    'attempt' => $activeAttempt
                ], 400);
            }

            // Create new attempt
            $studentQuiz = StudentQuiz::create([
                'user_id' => $userId,
                'quiz_id' => $quizId,
                'score' => null,
                'started_at' => now(),
                'completed_at' => null,
                'duration_seconds' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quiz started successfully',
                'attempt' => [
                    'id' => $studentQuiz->id,
                    'startedAt' => $studentQuiz->started_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit quiz answers
     */
    public function submitQuiz(Request $request, $quizId): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'answers' => 'required|array',
                'answers.*.questionId' => 'required|integer',
                'answers.*.selectedAnswer' => 'required|string',
                'duration' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Find active quiz attempt
            $studentQuiz = StudentQuiz::where('user_id', $userId)
                ->where('quiz_id', $quizId)
                ->whereNull('completed_at')
                ->first();

            if (!$studentQuiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active quiz attempt found'
                ], 404);
            }

            $answers = $request->answers;
            $correctCount = 0;
            $totalQuestions = count($answers);

            DB::beginTransaction();

            try {
                // Save each answer
                foreach ($answers as $answerData) {
                    $question = QuizQuestion::find($answerData['questionId']);

                    if ($question) {
                        $isCorrect = $answerData['selectedAnswer'] === $question->correct_option;

                        if ($isCorrect) {
                            $correctCount++;
                        }

                        StudentQuizAnswer::create([
                            'student_quiz_id' => $studentQuiz->id,
                            'question_id' => $question->id,
                            'selected_answer' => $answerData['selectedAnswer'],
                            'is_correct' => $isCorrect,
                            'correct_option_snapshot' => $question->correct_option,
                        ]);
                    }
                }

                // Calculate score and complete the quiz
                $score = round(($correctCount / $totalQuestions) * 100, 2);

                $studentQuiz->update([
                    'score' => $score,
                    'completed_at' => now(),
                    'duration_seconds' => $request->duration ?? 0,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Quiz submitted successfully',
                    'results' => [
                        'score' => $score,
                        'correct' => $correctCount,
                        'total' => $totalQuestions,
                        'percentage' => $score,
                        'attemptId' => $studentQuiz->id
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quiz results by attempt ID
     */
    public function getResults($attemptId): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $studentQuiz = StudentQuiz::where('id', $attemptId)
                ->where('user_id', $userId)
                ->with(['quiz.questions', 'answers.question'])
                ->first();

            if (!$studentQuiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz attempt not found'
                ], 404);
            }

            // Format results for frontend
            $questionResults = [];
            $totalCorrect = 0;

            foreach ($studentQuiz->answers as $answer) {
                $question = $answer->question;

                if ($answer->is_correct) {
                    $totalCorrect++;
                }

                $questionResults[] = [
                    'id' => $question->id,
                    'question' => $question->body,
                    'userAnswer' => $answer->selected_answer,
                    'correctAnswer' => $answer->correct_option_snapshot,
                    'isCorrect' => $answer->is_correct,
                    'options' => json_decode($question->options_json)
                ];
            }

            return response()->json([
                'success' => true,
                'results' => [
                    'quizTitle' => $studentQuiz->quiz->title,
                    'score' => $studentQuiz->score,
                    'totalQuestions' => $studentQuiz->quiz->questions->count(),
                    'correctAnswers' => $totalCorrect,
                    'startedAt' => $studentQuiz->started_at,
                    'completedAt' => $studentQuiz->completed_at,
                    'duration' => $studentQuiz->duration_seconds,
                    'questions' => $questionResults
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quiz results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quiz attempts for a user
     */
    public function getUserAttempts(): JsonResponse
    {
        try {
            $userId = Auth::id();

            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $attempts = StudentQuiz::where('user_id', $userId)
                ->with('quiz.chapter.subject')
                ->orderBy('created_at', 'desc')
                ->get();

            $formattedAttempts = $attempts->map(function($attempt) {
                return [
                    'id' => $attempt->id,
                    'quizTitle' => $attempt->quiz->title,
                    'subjectName' => $attempt->quiz->chapter->subject->title,
                    'chapterTitle' => $attempt->quiz->chapter->title,
                    'score' => $attempt->score,
                    'startedAt' => $attempt->started_at,
                    'completedAt' => $attempt->completed_at,
                    'duration' => $attempt->duration_seconds
                ];
            });

            return response()->json([
                'success' => true,
                'attempts' => $formattedAttempts
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch quiz attempts',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
