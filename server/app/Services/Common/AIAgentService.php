<?php

namespace App\Services\Common;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AIAgentService
{
    private $apiKey;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        if (!$this->apiKey) {
            throw new \RuntimeException('GEMINI_API_KEY not set in environment variables');
        }
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

            try {
                Log::info('Sending performance analysis prompt to Gemini API', [
                    'prompt_length' => strlen($prompt)
                ]);

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $textResponse = $data['candidates'][0]['content']['parts'][0]['text'];

                        // Clean the response by removing markdown code blocks if present
                        $cleanText = trim($textResponse);
                        if (str_starts_with($cleanText, '```json')) {
                            $cleanText = substr($cleanText, 7); // Remove ```json
                        }
                        if (str_starts_with($cleanText, '```')) {
                            $cleanText = substr($cleanText, 3); // Remove ```
                        }
                        if (str_ends_with($cleanText, '```')) {
                            $cleanText = substr($cleanText, 0, -3); // Remove ```
                        }
                        $cleanText = trim($cleanText);

                        // Try to parse JSON from the cleaned response
                        $jsonResponse = json_decode($cleanText, true);

                        if ($jsonResponse) {
                            return [
                                'success' => true,
                                'data' => $jsonResponse
                            ];
                        } else {
                            // Fallback response if JSON parsing fails
                            return [
                                'success' => true,
                                'data' => [
                                    'overall_performance' => 'Analysis completed but response format unexpected',
                                    'weak_areas' => [],
                                    'recommendations' => [],
                                    'study_plan' => $cleanText
                                ]
                            ];
                        }
                    } else {
                        throw new \Exception('No valid response from Gemini API');
                    }
                } else {
                    Log::error('Gemini API HTTP error', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    throw new \Exception('Gemini API returned error: ' . $response->status());
                }

            } catch (\Exception $e) {
                Log::error('Gemini API call failed: ' . $e->getMessage());

                // Fallback response
                return [
                    'success' => true,
                    'data' => [
                        'overall_performance' => 'Analysis could not be completed due to API error',
                        'weak_areas' => [],
                        'recommendations' => [],
                        'study_plan' => 'Please try again later'
                    ]
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

            try {
                Log::info('Sending lesson personalization prompt to Gemini API', [
                    'prompt_length' => strlen($prompt)
                ]);

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $textResponse = $data['candidates'][0]['content']['parts'][0]['text'];

                        // Clean the response by removing markdown code blocks if present
                        $cleanText = trim($textResponse);
                        if (str_starts_with($cleanText, '```json')) {
                            $cleanText = substr($cleanText, 7); // Remove ```json
                        }
                        if (str_starts_with($cleanText, '```')) {
                            $cleanText = substr($cleanText, 3); // Remove ```
                        }
                        if (str_ends_with($cleanText, '```')) {
                            $cleanText = substr($cleanText, 0, -3); // Remove ```
                        }
                        $cleanText = trim($cleanText);

                        // Try to parse JSON from the cleaned response
                        $jsonResponse = json_decode($cleanText, true);

                        if ($jsonResponse && isset($jsonResponse['lesson'])) {
                            return [
                                'success' => true,
                                'data' => $jsonResponse
                            ];
                        } else {
                            // Fallback response if JSON parsing fails
                            return [
                                'success' => true,
                                'data' => [
                                    'lesson' => [
                                        'title' => 'Personalized Lesson',
                                        'personalized_content' => $cleanText ?: 'Content could not be personalized',
                                        'learning_approach' => 'Standard approach',
                                        'practical_examples' => [],
                                        'next_steps' => []
                                    ]
                                ]
                            ];
                        }
                    } else {
                        throw new \Exception('No valid response from Gemini API');
                    }
                } else {
                    Log::error('Gemini API HTTP error for personalization', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    throw new \Exception('Gemini API returned error: ' . $response->status());
                }

            } catch (\Exception $e) {
                Log::error('Gemini API call failed for personalization: ' . $e->getMessage());

                // Fallback response
                return [
                    'success' => true,
                    'data' => [
                        'lesson' => [
                            'title' => 'Personalized Lesson',
                            'personalized_content' => 'Content could not be personalized due to API error',
                            'learning_approach' => 'Standard approach',
                            'practical_examples' => [],
                            'next_steps' => []
                        ]
                    ]
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
     * Analyze quiz performance and generate personalized feedback
     */
    public function analyzeQuizPerformance(array $analysisData)
    {
        try {
            // Prepare the analysis prompt
            $prompt = $this->buildQuizAnalysisPrompt($analysisData);

            try {
                Log::info('Sending quiz performance analysis prompt to Gemini API', [
                    'quiz_title' => $analysisData['quiz_title'],
                    'total_questions' => $analysisData['total_questions']
                ]);

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($this->baseUrl . '?key=' . $this->apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        $textResponse = $data['candidates'][0]['content']['parts'][0]['text'];

                        // Clean the response
                        $cleanText = trim($textResponse);
                        if (str_starts_with($cleanText, '```json')) {
                            $cleanText = substr($cleanText, 7);
                        }
                        if (str_starts_with($cleanText, '```')) {
                            $cleanText = substr($cleanText, 3);
                        }
                        if (str_ends_with($cleanText, '```')) {
                            $cleanText = substr($cleanText, 0, -3);
                        }
                        $cleanText = trim($cleanText);

                        // Parse JSON response
                        $jsonResponse = json_decode($cleanText, true);

                        if ($jsonResponse) {
                            return [
                                'success' => true,
                                'data' => $jsonResponse
                            ];
                        } else {
                            return [
                                'success' => true,
                                'data' => $this->generateFallbackAnalysis($analysisData)
                            ];
                        }
                    } else {
                        throw new \Exception('No valid response from Gemini API');
                    }
                } else {
                    Log::error('Gemini API HTTP error for quiz analysis', [
                        'status' => $response->status(),
                        'body' => $response->body()
                    ]);
                    throw new \Exception('Gemini API returned error: ' . $response->status());
                }

            } catch (\Exception $e) {
                Log::error('Gemini API call failed for quiz analysis: ' . $e->getMessage());

                // Return fallback analysis
                return [
                    'success' => true,
                    'data' => $this->generateFallbackAnalysis($analysisData)
                ];
            }

        } catch (\Exception $e) {
            Log::error('AI Agent quiz analysis error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Quiz analysis failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Build the quiz analysis prompt
     */
    private function buildQuizAnalysisPrompt(array $analysisData): string
    {
        $weakAreasText = "";
        foreach ($analysisData['weak_areas'] as $area) {
            $weakAreasText .= "- {$area['concept']}: {$area['accuracy']}% accuracy ({$area['missed']}/{$area['total']} missed)\n";
        }

        $studentProfile = $analysisData['student_profile'];
        $studentInfo = "Student Profile:\n";
        $studentInfo .= "- Name: {$studentProfile['name']}\n";
        $studentInfo .= "- Hobbies: " . ($studentProfile['hobbies'] ?: 'Not specified') . "\n";
        $studentInfo .= "- Learning Preferences: " . ($studentProfile['preferences'] ?: 'Not specified') . "\n";
        $studentInfo .= "- Bio: " . ($studentProfile['bio'] ?: 'Not specified') . "\n";

        $prompt = "You are an expert educational AI that analyzes quiz performance and provides personalized learning recommendations.

{$studentInfo}

Quiz Performance Summary:
- Quiz: {$analysisData['quiz_title']}
- Total Questions: {$analysisData['total_questions']}
- Correct Answers: {$analysisData['correct_answers']}
- Score: {$analysisData['score_percentage']}%
- Time Taken: " . ($analysisData['time_taken'] ? "{$analysisData['time_taken']} seconds" : 'Not recorded') . "

Weak Areas Identified:
{$weakAreasText}

Task:
Analyze this quiz performance and provide a comprehensive report that includes:

1. Overall performance assessment considering the student's profile and learning preferences
2. Detailed analysis of weak areas with specific recommendations
3. Personalized study plan based on the student's interests and learning style
4. Specific lesson recommendations to address knowledge gaps
5. Encouraging feedback tailored to the student's background

Respond in pure JSON with this exact structure:

{
  \"overall_performance\": \"Detailed assessment of overall performance, considering student profile and weak areas\",
  \"weak_areas\": [
    {
      \"concept\": \"concept_name\",
      \"description\": \"detailed explanation of the weak area\",
      \"missed\": 3,
      \"total\": 5,
      \"recommendations\": [
        \"Specific recommendation 1\",
        \"Specific recommendation 2\"
      ]
    }
  ],
  \"recommendations\": [
    {
      \"type\": \"study_method|resource|practice\",
      \"description\": \"Detailed recommendation\",
      \"priority\": \"high|medium|low\",
      \"personalization_reason\": \"Why this is recommended for this student\"
    }
  ],
  \"study_plan\": {
    \"duration_weeks\": 2,
    \"daily_study_time\": 30,
    \"schedule\": [
      {
        \"day\": \"Day 1\",
        \"focus\": \"Focus area\",
        \"activities\": [\"Activity 1\", \"Activity 2\"],
        \"estimated_time\": 30
      }
    ],
    \"milestones\": [\"Milestone 1\", \"Milestone 2\"]
  },
  \"recommended_lesson_ids\": [1, 2, 3],
  \"encouragement_message\": \"Personalized encouraging message based on student profile\"
}";

        return $prompt;
    }

    /**
     * Generate fallback analysis when AI is unavailable
     */
    private function generateFallbackAnalysis(array $analysisData): array
    {
        $score = $analysisData['score_percentage'];
        $weakAreas = $analysisData['weak_areas'];

        $overallPerformance = $this->getPerformanceDescription($score);

        $recommendations = [];
        foreach ($weakAreas as $area) {
            $recommendations[] = [
                'type' => 'practice',
                'description' => "Focus on practicing {$area['concept']} questions",
                'priority' => $area['accuracy'] < 50 ? 'high' : 'medium',
                'personalization_reason' => 'Based on quiz performance analysis'
            ];
        }

        return [
            'overall_performance' => $overallPerformance,
            'weak_areas' => $weakAreas,
            'recommendations' => $recommendations,
            'study_plan' => [
                'duration_weeks' => 2,
                'daily_study_time' => 30,
                'schedule' => [
                    [
                        'day' => 'Daily',
                        'focus' => 'Practice weak areas',
                        'activities' => ['Review concepts', 'Practice questions', 'Self-assessment'],
                        'estimated_time' => 30
                    ]
                ],
                'milestones' => ['Improve weak area scores', 'Complete practice sets']
            ],
            'recommended_lesson_ids' => [],
            'encouragement_message' => 'Keep practicing! Every expert was once a beginner.'
        ];
    }

    /**
     * Get performance description based on score
     */
    private function getPerformanceDescription(float $score): string
    {
        if ($score >= 90) {
            return "Excellent performance! You've demonstrated strong understanding of the material.";
        } elseif ($score >= 80) {
            return "Good performance! You have a solid foundation with room for improvement in specific areas.";
        } elseif ($score >= 70) {
            return "Fair performance. You understand the basics but need to strengthen your knowledge in key areas.";
        } elseif ($score >= 60) {
            return "Below average performance. Focus on understanding fundamental concepts and practice regularly.";
        } else {
            return "Needs significant improvement. Consider reviewing the basic concepts and seeking additional help.";
        }
    }

    /**
     * Check if AI agent is healthy
     */
    public function healthCheck()
    {
        try {
            Log::info('Testing Gemini API health check');

            // Simple health check by making a basic request to Gemini
            $testPrompt = "Say 'ok' if you can understand this message.";

            $response = Http::timeout(10)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $testPrompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 50,
                ]
            ]);

            Log::info('Gemini API response', [
                'status' => $response->status(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                Log::info('Gemini API health check successful');
                return true;
            } else {
                Log::error('Gemini API health check failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('AI Agent health check exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
