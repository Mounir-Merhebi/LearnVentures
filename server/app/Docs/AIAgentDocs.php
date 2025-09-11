<?php

namespace App\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *   schema="WrongAnswer",
 *   type="object",
 *   @OA\Property(property="question", type="string", example="What is 2 + 2?"),
 *   @OA\Property(property="user_answer", type="string", example="3"),
 *   @OA\Property(property="correct_answer", type="string", example="4"),
 *   @OA\Property(property="lesson_topic", type="string", example="Basic Arithmetic")
 * )
 *
 * @OA\Schema(
 *   schema="PerformanceAnalysis",
 *   type="object",
 *   @OA\Property(property="user_id", type="integer", example=1),
 *   @OA\Property(property="lesson_topic", type="string", example="Basic Arithmetic"),
 *   @OA\Property(property="analysis", type="string", example="The student shows difficulty with basic addition..."),
 *   @OA\Property(property="analyzed_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="PersonalizedLesson",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="user_id", type="integer", example=1),
 *   @OA\Property(property="lesson_id", type="integer", example=5),
 *   @OA\Property(property="personalized_title", type="string", example="Basic Arithmetic (Personalized)"),
 *   @OA\Property(property="personalized_content", type="string", example="Customized lesson content..."),
 *   @OA\Property(property="practical_examples", type="array", @OA\Items(type="string")),
 *   @OA\Property(property="generated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *   schema="PostQuizFeedback",
 *   type="object",
 *   @OA\Property(property="id", type="integer", example=1),
 *   @OA\Property(property="student_quiz_id", type="integer", example=10),
 *   @OA\Property(property="lesson_id", type="integer", example=5),
 *   @OA\Property(property="overall_performance", type="string", example="Good performance with room for improvement"),
 *   @OA\Property(property="weak_areas", type="array", @OA\Items(type="string")),
 *   @OA\Property(property="recommendations", type="array", @OA\Items(type="string")),
 *   @OA\Property(property="study_plan", type="string", example="Focus on multiplication tables..."),
 *   @OA\Property(property="recommended_lesson_ids", type="array", @OA\Items(type="integer")),
 *   @OA\Property(property="analyzed_at", type="string", format="date-time")
 * )
 */
class AIAgentDocs
{
    /**
     * @OA\Get(
     *   path="/api/v0.1/ai-health",
     *   summary="AI Agent health check",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="AI agent is healthy",
     *     @OA\JsonContent(
     *       @OA\Property(property="ai_agent_status", type="string", example="healthy"),
     *       @OA\Property(property="response_status", type="integer", example=200)
     *     )
     *   ),
     *   @OA\Response(
     *     response=503,
     *     description="AI agent is unavailable",
     *     @OA\JsonContent(
     *       @OA\Property(property="ai_agent_status", type="string", example="unavailable"),
     *       @OA\Property(property="response_status", type="integer", example=500)
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Health check failed",
     *     @OA\JsonContent(
     *       @OA\Property(property="ai_agent_status", type="string", example="error"),
     *       @OA\Property(property="error", type="string", example="Connection timeout")
     *     )
     *   )
     * )
     */
    public function healthCheck() {}

    /**
     * @OA\Get(
     *   path="/api/v0.1/test-health",
     *   summary="Test AI Agent health with detailed response",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Health check successful",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="ai_agent_status", type="string", example="healthy"),
     *       @OA\Property(property="response_status", type="integer", example=200),
     *       @OA\Property(property="api_key_exists", type="boolean", example=true)
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Health check failed",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="error", type="string", example="Connection timeout"),
     *       @OA\Property(property="api_key_exists", type="boolean", example=true)
     *     )
     *   )
     * )
     */
    public function testHealth() {}

    /**
     * @OA\Post(
     *   path="/api/v0.1/user/analyze-performance",
     *   summary="Analyze user performance based on wrong answers",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"user_id","lesson_topic","wrong_answers"},
     *       @OA\Property(property="user_id", type="integer", example=1),
     *       @OA\Property(property="lesson_topic", type="string", example="Basic Arithmetic"),
     *       @OA\Property(
     *         property="wrong_answers",
     *         type="array",
     *         @OA\Items(
     *           type="object",
     *           required={"question","user_answer","correct_answer"},
     *           @OA\Property(property="question", type="string", example="What is 2 + 2?"),
     *           @OA\Property(property="user_answer", type="string", example="3"),
     *           @OA\Property(property="correct_answer", type="string", example="4")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Performance analysis completed",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Performance analysis completed"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="analysis", type="string", example="The student shows difficulty with basic arithmetic..."),
     *         @OA\Property(property="lesson_topic", type="string", example="Basic Arithmetic"),
     *         @OA\Property(property="questions_analyzed", type="integer", example=5)
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Analysis failed",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Analysis failed: AI service unavailable")
     *     )
     *   )
     * )
     */
    public function analyzePerformance() {}

    /**
     * @OA\Post(
     *   path="/api/v0.1/user/personalize-lesson",
     *   summary="Generate personalized lesson for user",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"user_id","lesson_id"},
     *       @OA\Property(property="user_id", type="integer", example=1),
     *       @OA\Property(property="lesson_id", type="integer", example=5)
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lesson personalized successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Lesson personalized successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="personalized_lesson_id", type="integer", example=1),
     *         @OA\Property(
     *           property="original_lesson",
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=5),
     *           @OA\Property(property="title", type="string", example="Introduction to Algebra"),
     *           @OA\Property(property="content", type="string", example="Original lesson content...")
     *         ),
     *         @OA\Property(
     *           property="user",
     *           type="object",
     *           @OA\Property(property="id", type="integer", example=1),
     *           @OA\Property(property="name", type="string", example="John Doe"),
     *           @OA\Property(property="hobbies", type="string", example="Reading, Gaming"),
     *           @OA\Property(property="preferences", type="string", example="Visual learning"),
     *           @OA\Property(property="bio", type="string", example="A passionate learner")
     *         ),
     *         @OA\Property(
     *           property="personalized_lesson",
     *           type="object",
     *           @OA\Property(property="title", type="string", example="Algebra for Visual Learners"),
     *           @OA\Property(property="personalized_content", type="string", example="Customized content based on visual learning preferences..."),
     *           @OA\Property(property="practical_examples", type="array", @OA\Items(type="string"))
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Personalization failed",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Lesson personalization failed"),
     *       @OA\Property(property="error", type="string", example="AI service unavailable")
     *     )
     *   )
     * )
     */
    public function personalizeLesson() {}

    /**
     * @OA\Get(
     *   path="/api/v0.1/user/analyses/{userId}",
     *   summary="Get user's performance analyses",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1),
     *     description="User ID"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Analyses retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/PerformanceAnalysis")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Failed to fetch analyses",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Failed to fetch analyses: Database error")
     *     )
     *   )
     * )
     */
    public function getUserAnalyses() {}

    /**
     * @OA\Get(
     *   path="/api/v0.1/user/personalized-lessons/{userId}",
     *   summary="Get user's personalized lessons",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1),
     *     description="User ID"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Lessons retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/PersonalizedLesson")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Failed to fetch lessons",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Failed to fetch lessons: Database error")
     *     )
     *   )
     * )
     */
    public function getUserLessons() {}

    /**
     * @OA\Get(
     *   path="/api/v0.1/user/wrong-answers/{userId}",
     *   summary="Get user's wrong answers",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=1),
     *     description="User ID"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Wrong answers retrieved successfully",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/WrongAnswer")
     *     )
     *   )
     * )
     */
    public function getWrongAnswers() {}

    /**
     * @OA\Post(
     *   path="/api/v0.1/quiz/analyze-performance",
     *   summary="Analyze quiz performance and generate AI feedback",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"student_quiz_id"},
     *       @OA\Property(property="student_quiz_id", type="integer", example=10)
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Quiz performance analysis completed",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Quiz performance analysis completed"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="feedback_id", type="integer", example=1),
     *         @OA\Property(
     *           property="analysis",
     *           type="object",
     *           @OA\Property(property="overall_performance", type="string", example="Good performance with room for improvement"),
     *           @OA\Property(property="weak_areas", type="array", @OA\Items(type="string")),
     *           @OA\Property(property="recommendations", type="array", @OA\Items(type="string")),
     *           @OA\Property(property="study_plan", type="string", example="Focus on multiplication tables..."),
     *           @OA\Property(property="recommended_lesson_ids", type="array", @OA\Items(type="integer"))
     *         ),
     *         @OA\Property(property="quiz_title", type="string", example="Mathematics Quiz 1"),
     *         @OA\Property(property="score", type="number", format="float", example=85.5),
     *         @OA\Property(property="total_questions", type="integer", example=20)
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Quiz must be completed before analysis",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Quiz must be completed before analysis")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Quiz attempt not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Quiz attempt not found or access denied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Analysis failed",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Quiz analysis failed: AI service unavailable")
     *     )
     *   )
     * )
     */
    public function analyzeQuizPerformance() {}

    /**
     * @OA\Get(
     *   path="/api/v0.1/quiz/feedback/{studentQuizId}",
     *   summary="Get quiz feedback for a student",
     *   tags={"AI Agent"},
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(
     *     name="studentQuizId",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer", example=10),
     *     description="Student Quiz ID"
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Quiz feedback retrieved successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="data", ref="#/components/schemas/PostQuizFeedback")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Quiz feedback not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Quiz feedback not found or access denied")
     *     )
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Failed to retrieve feedback",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Failed to retrieve quiz feedback: Database error")
     *     )
     *   )
     * )
     */
    public function getQuizFeedback() {}
}
