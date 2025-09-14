<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Services\Common\AIAgentService;
use App\Models\User;
use App\Models\WrongAnswer;
use App\Models\PerformanceAnalysis;
use App\Models\PersonalizedLesson;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\StudentQuiz;
use App\Models\StudentQuizAnswer;
use App\Models\PostQuizFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AIAgentController extends Controller
{
    protected $aiAgentService;

    public function __construct(AIAgentService $aiAgentService)
    {
        $this->aiAgentService = $aiAgentService;
    }

    /**
     * Test method without service dependency
     */
    public function testHealth()
    {
        try {
            // Test Gemini API directly
            $apiKey = env('GEMINI_API_KEY');
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

            $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '?key=' . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Say ok']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 10,
                ]
            ]);

            return response()->json([
                'status' => 'success',
                'ai_agent_status' => $response->successful() ? 'healthy' : 'unavailable',
                'response_status' => $response->status(),
                'api_key_exists' => !empty($apiKey)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
                'api_key_exists' => !empty(env('GEMINI_API_KEY'))
            ], 500);
        }
    }

    public function getWrongAnswers($userId)
{
    $wrongAnswers = WrongAnswer::where('user_id', $userId)->get([
        'question',
        'user_answer',
        'correct_answer',
        'lesson_topic'
    ]);
    return response()->json($wrongAnswers);
}
    /**
     * Analyze user performance based on wrong answers
     */
    public function analyzePerformance(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'lesson_topic' => 'required|string',
                'wrong_answers' => 'required|array|min:1',
                'wrong_answers.*.question' => 'required|string',
                'wrong_answers.*.user_answer' => 'required|string',
                'wrong_answers.*.correct_answer' => 'required|string',
            ]);

            // Format data for the service (add lesson_topic to each answer)
            $formattedAnswers = [];
            foreach ($request->wrong_answers as $answer) {
                $formattedAnswers[] = [
                    'lesson_topic' => $request->lesson_topic,
                    'question' => $answer['question'],
                    'user_answer' => $answer['user_answer'],
                    'correct_answer' => $answer['correct_answer']
                ];
            }

            // Call the service method - this will use the detailed prompt
            $result = $this->aiAgentService->analyzePerformance($formattedAnswers);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Performance analysis completed',
                    'data' => [
                        'analysis' => $result['data'],
                        'lesson_topic' => $request->lesson_topic,
                        'questions_analyzed' => count($request->wrong_answers)
                    ]
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Analysis failed: ' . $result['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Analysis failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate personalized lesson for user
     */
    public function personalizeLesson(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'lesson_id' => 'required|exists:lessons,id',
            ]);

            // Fetch the lesson from database
            $lesson = Lesson::findOrFail($request->lesson_id);

            // Fetch user data
            $user = User::findOrFail($request->user_id);

            // Prepare user preferences from user profile
            $preferences = [
                'hobbies' => $user->hobbies ?? 'general learning',
                'preferred_learning_style' => $user->preferences ?? 'mixed approach',
                'bio' => $user->bio ?? 'learner'
            ];

            // Call AI agent to personalize the lesson
            $result = $this->aiAgentService->personalizeLesson(
                $preferences,
                $lesson->content
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson personalization failed',
                    'error' => $result['error']
                ], 500);
            }

            // Save personalized lesson to database
            $lessonData = $result['data']['lesson'];
            $personalizedLesson = PersonalizedLesson::create([
                'user_id' => $request->user_id,
                'lesson_id' => $request->lesson_id,
                'personalized_title' => $lessonData['title'] ?? $lesson->title . ' (Personalized)',
                'personalized_content' => $lessonData['personalized_content'],
                'practical_examples' => $lessonData['practical_examples'] ?? [],
                'generated_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson personalized successfully',
                'data' => [
                    'personalized_lesson_id' => $personalizedLesson->id,
                    'original_lesson' => [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'content' => $lesson->content,
                    ],
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'hobbies' => $user->hobbies,
                        'preferences' => $user->preferences,
                        'bio' => $user->bio,
                    ],
                    'personalized_lesson' => $lessonData
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personalization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's performance analyses
     */
    public function getUserAnalyses(Request $request, $userId)
    {
        try {
            $analyses = PerformanceAnalysis::where('user_id', $userId)
                ->with('wrongAnswers')
                ->orderBy('analyzed_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $analyses
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analyses: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's personalized lessons
     */
    public function getUserLessons(Request $request, $userId)
    {
        try {
            $lessons = PersonalizedLesson::where('user_id', $userId)
                ->orderBy('generated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $lessons
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch lessons: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check for AI agent
     */
    public function healthCheck()
    {
        try {
            // Test Gemini API directly
            $apiKey = env('GEMINI_API_KEY');
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

            $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '?key=' . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Say ok']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 10,
                ]
            ]);

            $isHealthy = $response->successful();

            return response()->json([
                'ai_agent_status' => $isHealthy ? 'healthy' : 'unavailable',
                'response_status' => $response->status()
            ], $isHealthy ? 200 : 503);
        } catch (\Exception $e) {
            return response()->json([
                'ai_agent_status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyze quiz performance and generate AI feedback
     */
    public function analyzeQuizPerformance(Request $request)
    {
        try {
            $request->validate([
                'student_quiz_id' => 'required|exists:student_quizzes,id',
            ]);

            $user = Auth::user();
            $studentQuizId = $request->student_quiz_id;

            // Fetch the student's quiz attempt with all related data
            $studentQuiz = StudentQuiz::where('id', $studentQuizId)
                ->where('user_id', $user->id)
                ->with(['quiz', 'answers.question', 'user'])
                ->first();

            if (!$studentQuiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz attempt not found or access denied'
                ], 404);
            }

            if (!$studentQuiz->completed_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz must be completed before analysis'
                ], 400);
            }

            // Check if analysis already exists
            $existingFeedback = PostQuizFeedback::where('student_quiz_id', $studentQuizId)->first();
            if ($existingFeedback) {
                return response()->json([
                    'success' => true,
                    'message' => 'Analysis already exists',
                    'data' => $existingFeedback
                ]);
            }

            // Prepare analysis data
            $analysisData = $this->prepareQuizAnalysisData($studentQuiz);

            // Generate AI analysis
            $result = $this->aiAgentService->analyzeQuizPerformance($analysisData);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate quiz analysis',
                    'error' => $result['error']
                ], 500);
            }

            // Store the feedback
            $feedback = PostQuizFeedback::create([
                'student_quiz_id' => $studentQuizId,
                'lesson_id' => $studentQuiz->quiz->lesson_id,
                'overall_performance' => $result['data']['overall_performance'],
                'weak_areas' => $result['data']['weak_areas'],
                'recommendations' => $result['data']['recommendations'],
                'study_plan' => $result['data']['study_plan'],
                'recommended_lesson_ids' => $result['data']['recommended_lesson_ids'],
                'analyzed_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quiz performance analysis completed',
                'data' => [
                    'feedback_id' => $feedback->id,
                    'analysis' => $result['data'],
                    'quiz_title' => $studentQuiz->quiz->title,
                    'score' => $studentQuiz->score,
                    'total_questions' => $studentQuiz->answers->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz analysis failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare quiz analysis data for AI processing
     */
    private function prepareQuizAnalysisData(StudentQuiz $studentQuiz): array
    {
        $answers = $studentQuiz->answers->map(function ($answer) {
            return [
                'question_id' => $answer->question_id,
                'question_text' => $answer->question->body,
                'selected_answer' => $answer->selected_answer,
                'correct_answer' => $answer->question->correct_option,
                'is_correct' => $answer->is_correct,
                'concept_slug' => $answer->question->concept_slug,
                'options' => json_decode($answer->question->options_json, true)
            ];
        });

        // Group by concept for analysis
        $conceptPerformance = [];
        foreach ($answers as $answer) {
            $concept = $answer['concept_slug'];
            if (!isset($conceptPerformance[$concept])) {
                $conceptPerformance[$concept] = [
                    'total' => 0,
                    'correct' => 0,
                    'questions' => []
                ];
            }
            $conceptPerformance[$concept]['total']++;
            if ($answer['is_correct']) {
                $conceptPerformance[$concept]['correct']++;
            }
            $conceptPerformance[$concept]['questions'][] = $answer;
        }

        // Calculate concept-wise performance
        $weakAreas = [];
        foreach ($conceptPerformance as $concept => $data) {
            $accuracy = $data['total'] > 0 ? ($data['correct'] / $data['total']) * 100 : 0;
            if ($accuracy < 70) { // Consider below 70% as weak
                $weakAreas[] = [
                    'concept' => $concept,
                    'accuracy' => round($accuracy, 2),
                    'missed' => $data['total'] - $data['correct'],
                    'total' => $data['total']
                ];
            }
        }

        return [
            'quiz_title' => $studentQuiz->quiz->title,
            'total_questions' => $answers->count(),
            'correct_answers' => $answers->where('is_correct', true)->count(),
            'score_percentage' => $studentQuiz->score,
            'time_taken' => $studentQuiz->duration_seconds,
            'answers' => $answers->toArray(),
            'concept_performance' => $conceptPerformance,
            'weak_areas' => $weakAreas,
            'student_profile' => [
                'name' => $studentQuiz->user->name,
                'hobbies' => $studentQuiz->user->hobbies,
                'preferences' => $studentQuiz->user->preferences,
                'bio' => $studentQuiz->user->bio
            ]
        ];
    }

    /**
     * Get quiz feedback for a student
     */
    public function getQuizFeedback(Request $request, $studentQuizId)
    {
        try {
            $user = Auth::user();

            $feedback = PostQuizFeedback::where('student_quiz_id', $studentQuizId)
                ->whereHas('studentQuiz', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with(['studentQuiz.quiz', 'lesson'])
                ->first();

            if (!$feedback) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz feedback not available. Analysis may still be processing.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $feedback
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve quiz feedback: ' . $e->getMessage()
            ], 500);
        }
    }
}