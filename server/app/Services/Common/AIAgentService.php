<?php

namespace App\Services\Common;

use Illuminate\Support\Facades\Log;
use Gemini\Client;
use Gemini\Data\GenerationConfig;
use Gemini\Enums\ModelName;
use Gemini\Enums\ResponseMimeType;

class AIAgentService
{
    private $client;
    private $modelName;

    public function __construct()
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            throw new \RuntimeException('GEMINI_API_KEY not set in environment variables');
        }

        $this->client = new Client($apiKey);
        $this->modelName = ModelName::GEMINI_1_5_FLASH;
    }

    /**
     * Analyze student performance based on wrong answers
     */
    public function analyzePerformance(array $wrongAnswers)
    {
        try {
            // Format the wrong answers for the prompt
            $answersText = "";
            foreach ($wrongAnswers as $i => $answer) {
                $answersText .= "
Question " . ($i + 1) . ":
- Topic: {$answer['lesson_topic']}
- Question: {$answer['question']}
- User's Answer: {$answer['user_answer']}
- Correct Answer: {$answer['correct_answer']}
";
            }

            $prompt = "You are an expert educational AI that analyzes student performance and provides personalized study recommendations.

Wrong Answers Analysis:
{$answersText}

Task:
Analyze these wrong answers and provide:
1. Overall performance assessment
2. Identify weak areas and knowledge gaps
3. Suggest specific topics to focus on or revise
4. Create a personalized study plan
5. Provide specific resources and practice exercises

Respond in pure JSON matching this schema:

{
  \"overall_performance\": \"Brief assessment of overall performance (e.g., 'Good understanding of basics but needs work on advanced concepts')\",
  \"weak_areas\": [
    \"Area 1 that needs improvement\",
    \"Area 2 that needs improvement\",
    \"Area 3 that needs improvement\"
  ],
  \"recommendations\": [
    {
      \"topic\": \"Specific topic to focus on\",
      \"reason\": \"Why this topic needs attention based on wrong answers\",
      \"priority\": \"high|medium|low\",
      \"suggested_resources\": [
        \"Resource 1 for this topic\",
        \"Resource 2 for this topic\"
      ],
      \"practice_exercises\": [
        \"Exercise 1 to practice this topic\",
        \"Exercise 2 to practice this topic\"
      ]
    }
  ],
  \"study_plan\": \"A step-by-step study plan to address the identified weaknesses\"
}";

            $config = new GenerationConfig(
                responseMimeType: ResponseMimeType::APPLICATION_JSON
            );

            $response = $this->client->generateContent($this->modelName, $prompt, $config);

            $jsonResponse = json_decode($response->text(), true);

            if ($jsonResponse) {
                return [
                    'success' => true,
                    'data' => $jsonResponse
                ];
            } else {
                // Fallback response if JSON parsing fails
                $fallbackData = [
                    'overall_performance' => 'Analysis could not be completed',
                    'weak_areas' => [],
                    'recommendations' => [],
                    'study_plan' => 'Please try again with more specific wrong answers'
                ];

                return [
                    'success' => true,
                    'data' => $fallbackData
                ];
            }

        } catch (\Exception $e) {
            Log::error('AI Agent performance analysis error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'AI analysis failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get personalized lesson based on user preferences
     */
    public function personalizeLesson(array $preferences, string $lessonText)
    {
        try {
            $prompt = "You are an expert educational AI that personalizes lessons based on user preferences.

User Profile:
- Hobbies: {$preferences['hobbies']}
- Preferred Learning Style: {$preferences['preferred_learning_style']}
- Bio: {$preferences['bio']}

Original Lesson:
{$lessonText}

Task:
Create a personalized version of this lesson that:
1. Adapts the content to match their learning style
2. Incorporates examples from their hobbies and interests
3. Uses language and references that resonate with their background
4. Provides practical examples they can relate to
5. Suggests next steps based on their profile

Respond in pure JSON matching this schema:

{
  \"lesson\": {
    \"title\": \"Personalized lesson title\",
    \"personalized_content\": \"The main lesson content adapted to their preferences\",
    \"learning_approach\": \"Explanation of how this lesson is tailored to their learning style\",
    \"practical_examples\": [
      \"Example 1 related to their hobbies\",
      \"Example 2 related to their interests\",
      \"Example 3 that connects to their background\"
    ],
    \"next_steps\": [
      \"Step 1 based on their profile\",
      \"Step 2 tailored to their interests\",
      \"Step 3 that builds on their background\"
    ]
  }
}";

            $config = new GenerationConfig(
                responseMimeType: ResponseMimeType::APPLICATION_JSON
            );

            $response = $this->client->generateContent($this->modelName, $prompt, $config);

            $jsonResponse = json_decode($response->text(), true);

            if ($jsonResponse && isset($jsonResponse['lesson'])) {
                return [
                    'success' => true,
                    'data' => $jsonResponse
                ];
            } else {
                // Fallback response if JSON parsing fails
                $fallbackData = [
                    'lesson' => [
                        'title' => 'Personalized Lesson',
                        'personalized_content' => 'Content could not be personalized',
                        'learning_approach' => 'Standard approach',
                        'practical_examples' => [],
                        'next_steps' => []
                    ]
                ];

                return [
                    'success' => true,
                    'data' => $fallbackData
                ];
            }

        } catch (\Exception $e) {
            Log::error('AI Agent lesson personalization error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lesson personalization failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if AI agent is healthy
     */
    public function healthCheck()
    {
        try {
            // Simple health check by making a basic request to Gemini
            $testPrompt = "Say 'ok' if you can understand this message.";
            $response = $this->client->generateContent($this->modelName, $testPrompt);
            return !empty($response->text());
        } catch (\Exception $e) {
            Log::error('AI Agent health check failed: ' . $e->getMessage());
            return false;
        }
    }
}
