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

            $userId = $request->user_id;
            $lessonTopic = $request->lesson_topic;
            $wrongAnswersData = $request->wrong_answers;

            // Save wrong answers to database
            $wrongAnswers = [];
            foreach ($wrongAnswersData as $answerData) {
                $wrongAnswer = WrongAnswer::create([
                    'user_id' => $userId,
                    'lesson_topic' => $lessonTopic,
                    'question' => $answerData['question'],
                    'user_answer' => $answerData['user_answer'],
                    'correct_answer' => $answerData['correct_answer'],
                    'analyzed' => false
                ]);
                $wrongAnswers[] = $wrongAnswer;
            }

            // Format data for AI agent
            $aiPayload = [];
            foreach ($wrongAnswersData as $answerData) {
                $aiPayload[] = [
                    'question' => $answerData['question'],
                    'user_answer' => $answerData['user_answer'],
                    'correct_answer' => $answerData['correct_answer'],
                    'lesson_topic' => $lessonTopic
                ];
            }

            // Call AI agent
            $result = $this->aiAgentService->analyzePerformance($aiPayload);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'AI analysis failed',
                    'error' => $result['error']
                ], 500);
            }

            // Save analysis results to database
            $analysisData = $result['data'];
            $performanceAnalysis = PerformanceAnalysis::create([
                'user_id' => $userId,
                'lesson_topic' => $lessonTopic,
                'overall_performance' => $analysisData['overall_performance'],
                'weak_areas' => $analysisData['weak_areas'],
                'recommendations' => $analysisData['recommendations'],
                'study_plan' => $analysisData['study_plan'],
            ]);

            // Update wrong answers as analyzed
            foreach ($wrongAnswers as $wrongAnswer) {
                $wrongAnswer->update([
                    'analyzed' => true,
                    'performance_analysis_id' => $performanceAnalysis->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Performance analysis completed',
                'data' => [
                    'analysis_id' => $performanceAnalysis->id,
                    'analysis' => $analysisData
                ]
            ], 200);

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
        $isHealthy = $this->aiAgentService->healthCheck();
        
        return response()->json([
            'ai_agent_status' => $isHealthy ? 'healthy' : 'unavailable'
        ], $isHealthy ? 200 : 503);
    }
}