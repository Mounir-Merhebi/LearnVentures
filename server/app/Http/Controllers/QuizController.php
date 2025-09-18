<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\StudentQuiz;
use App\Models\StudentQuizAnswer;
use App\Models\PostQuizFeedback;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    /**
     * Analyze a quiz attempt and generate AI feedback (OpenAI with fallback), then persist to post_quiz_feedback
     */
    public function analyzePerformance(Request $request): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $data = $request->validate([
            'student_quiz_id' => 'required|integer',
        ]);

        $attempt = StudentQuiz::with(['answers.question', 'quiz'])
            ->where('id', $data['student_quiz_id'])
            ->where('user_id', $userId)
            ->first();

        if (!$attempt) {
            return response()->json(['success' => false, 'message' => 'Quiz attempt not found'], 404);
        }

        // If feedback already exists, return it
        $existing = PostQuizFeedback::where('student_quiz_id', $attempt->id)->first();
        if ($existing) {
            return response()->json(['success' => true, 'data' => $existing]);
        }

        // Prepare data for analysis (only wrong answers sent to AI)
        $total = max(1, $attempt->answers->count());
        $wrong = $attempt->answers->where('is_correct', false);
        $wrongItems = [];
        foreach ($wrong as $ans) {
            $q = $ans->question;
            if ($q) {
                $wrongItems[] = [
                    'question' => $q->body,
                    'user_answer' => $ans->selected_answer,
                    'correct_answer' => $ans->correct_option_snapshot,
                    'concept' => $q->concept_slug ?? null,
                ];
            }
        }

        // Try OpenAI first
        $aiPayload = null;
        $openaiApiKey = env('OPENAI_API_KEY');
        if ($openaiApiKey) {
            $instructions = [
                'role' => 'system',
                'content' => 'You are an educational assistant that returns STRICT JSON only. Analyze the student\'s quiz performance using the wrong answers provided. Output a compact JSON object with keys: overall_performance (string), weak_areas (array of objects with concept, description, missed, total), recommendations (array of objects with type, description, priority), study_plan (object with duration_weeks, daily_study_time, schedule array of {day, focus, estimated_time}), recommended_lesson_ids (array of integers). Do NOT include any additional text outside the JSON.'
            ];

            $userMsg = [
                'role' => 'user',
                'content' => json_encode([
                    'quiz_title' => $attempt->quiz->title ?? 'Quiz',
                    'score' => $attempt->score,
                    'wrong_answers' => $wrongItems,
                    'total_questions' => $total,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ];

            try {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$openaiApiKey}",
                    'Content-Type' => 'application/json',
                ])->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('QUIZ_FEEDBACK_MODEL', 'gpt-3.5-turbo'),
                    'messages' => [$instructions, $userMsg],
                    'temperature' => 0.2,
                    'max_tokens' => 600,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['choices'][0]['message']['content'] ?? '';

                    // Attempt to extract JSON (in case the model wraps it)
                    $jsonText = $text;
                    $first = strpos($jsonText, '{');
                    $last = strrpos($jsonText, '}');
                    if ($first !== false && $last !== false && $last >= $first) {
                        $jsonText = substr($jsonText, $first, $last - $first + 1);
                    }
                    $decoded = json_decode($jsonText, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // Normalize minutes fields to integers so the UI doesn't double-append units
                        $normalizeMinutes = function ($value) {
                            if (is_numeric($value)) {
                                return (int)$value;
                            }
                            if (is_string($value)) {
                                if (preg_match('/(\\d+(?:\\.\\d+)?)\\s*(hour|hours|hr|h)/i', $value, $m)) {
                                    return (int)round(((float)$m[1]) * 60);
                                }
                                if (preg_match('/(\\d+(?:\\.\\d+)?)\\s*(min|mins|minute|minutes|m)/i', $value, $m)) {
                                    return (int)round((float)$m[1]);
                                }
                                if (preg_match('/\\d+/', $value, $m)) {
                                    return (int)$m[0];
                                }
                            }
                            return null;
                        };

                        $planIn = isset($decoded['study_plan']) && is_array($decoded['study_plan']) ? $decoded['study_plan'] : [];
                        $scheduleIn = isset($planIn['schedule']) && is_array($planIn['schedule']) ? $planIn['schedule'] : [];
                        $schedule = [];
                        foreach ($scheduleIn as $item) {
                            $schedule[] = [
                                'day' => isset($item['day']) && is_string($item['day']) ? $item['day'] : '',
                                'focus' => isset($item['focus']) && is_string($item['focus']) ? $item['focus'] : '',
                                'estimated_time' => $normalizeMinutes($item['estimated_time'] ?? null),
                            ];
                        }

                        $studyPlan = [
                            'duration_weeks' => (int)($planIn['duration_weeks'] ?? 1),
                            'daily_study_time' => $normalizeMinutes($planIn['daily_study_time'] ?? null),
                            'schedule' => $schedule,
                        ];

                        $aiPayload = [
                            'student_quiz_id' => $attempt->id,
                            'chapter_id' => $attempt->quiz->chapter_id ?? null,
                            'overall_performance' => $decoded['overall_performance'] ?? null,
                            'weak_areas' => $decoded['weak_areas'] ?? [],
                            'recommendations' => $decoded['recommendations'] ?? [],
                            'study_plan' => $studyPlan,
                            'recommended_lesson_ids' => $decoded['recommended_lesson_ids'] ?? [],
                            'analyzed_at' => now(),
                        ];
                        Log::info('OpenAI quiz feedback generated', [
                            'attempt_id' => $attempt->id,
                        ]);
                    } else {
                        Log::warning('OpenAI feedback JSON parse failed; falling back', [
                            'content_preview' => substr($text, 0, 200)
                        ]);
                    }
                } else {
                    Log::error('OpenAI API error for quiz feedback', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('OpenAI API call failed for quiz feedback', ['error' => $e->getMessage()]);
            }
        }

        // If AI failed, return error (no heuristic fallback)
        if (!$aiPayload) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate AI feedback. Please try again shortly.',
            ], 502);
        }

        $payload = $aiPayload;

        $feedback = PostQuizFeedback::create($payload);
        $generatedBy = 'openai';

        return response()->json([
            'success' => true,
            'data' => $feedback,
            'generatedBy' => $generatedBy,
            'message' => 'Feedback generated',
        ]);
    }

    /**
     * Fetch stored feedback for a quiz attempt
     */
    public function getFeedback($studentQuizId): JsonResponse
    {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['success' => false, 'message' => 'User not authenticated'], 401);
        }

        $attempt = StudentQuiz::where('id', $studentQuizId)
            ->where('user_id', $userId)
            ->first();

        if (!$attempt) {
            return response()->json(['success' => false, 'message' => 'Quiz attempt not found'], 404);
        }

        $feedback = PostQuizFeedback::where('student_quiz_id', $attempt->id)->first();
        if (!$feedback) {
            return response()->json(['success' => false, 'message' => 'Feedback not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $feedback]);
    }
}
