<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Services\Common\AIAgentService;
use App\Models\User;
use App\Models\WrongAnswer;
use App\Models\PerformanceAnalysis;
use App\Models\PersonalizedLesson;
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
                'original_lesson_title' => 'required|string',
                'original_lesson_content' => 'required|string',
            ]);

            $user = User::findOrFail($request->user_id);

            // Prepare user preferences from user profile
            $preferences = [
                'hobbies' => $user->hobbies ?? 'general learning',
                'preferred_learning_style' => $user->preferences ?? 'mixed approach',
                'bio' => $user->bio ?? 'learner'
            ];

            // Call AI agent
            $result = $this->aiAgentService->personalizeLesson(
                $preferences,
                $request->original_lesson_content
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
                'original_lesson_title' => $request->original_lesson_title,
                'original_lesson_content' => $request->original_lesson_content,
                'personalized_title' => $lessonData['title'],
                'personalized_content' => $lessonData['personalized_content'],
                'learning_approach' => $lessonData['learning_approach'],
                'practical_examples' => $lessonData['practical_examples'],
                'next_steps' => $lessonData['next_steps'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson personalized successfully',
                'data' => [
                    'lesson_id' => $personalizedLesson->id,
                    'lesson' => $lessonData
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
}