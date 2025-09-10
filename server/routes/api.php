<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\AuthController;
use App\Http\Controllers\Common\AIAgentController;
use App\Http\Controllers\ChatController;

Route::group(["prefix" =>"v0.1"], function(){
    Route::group(["middleware" => "auth:api"], function(){
        Route::group(["prefix" => "user"], function(){
            // AI Agent endpoints
            Route::post('/analyze-performance', [AIAgentController::class, 'analyzePerformance']);
            Route::post('/personalize-lesson', [AIAgentController::class, 'personalizeLesson']);
            Route::get('/analyses/{userId}', [AIAgentController::class, 'getUserAnalyses']);
            Route::get('/personalized-lessons/{userId}', [AIAgentController::class, 'getUserLessons']);
            Route::get('/wrong-answers/{userId}', [AIAgentController::class, 'getWrongAnswers']);
        });
        
        // AI Agent health check
        Route::get('/ai-health', [AIAgentController::class, 'healthCheck']);
        Route::get('/test-health', [AIAgentController::class, 'testHealth']);

        // Chat endpoints
        Route::post('/chat/sessions', [ChatController::class, 'createSession']);
        Route::post('/chat/messages', [ChatController::class, 'sendMessage']);

        // Direct health check route
        Route::get('/direct-health', function () {
            try {
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
        });

        // Simple Gemini test route
        Route::get('/test-gemini', function () {
            try {
                $apiKey = env('GEMINI_API_KEY');
                $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

                $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($baseUrl . '?key=' . $apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Say hello world']
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 50,
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response';
                    return response()->json([
                        'success' => true,
                        'message' => $text,
                        'api_key_exists' => !empty($apiKey)
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'api_key_exists' => !empty($apiKey)
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'api_key_exists' => !empty(env('GEMINI_API_KEY'))
                ], 500);
            }
        });

        // Test performance analysis without database
        Route::post('/test-analysis', function (\Illuminate\Http\Request $request) {
            try {
                $request->validate([
                    'question' => 'required|string',
                    'user_answer' => 'required|string',
                    'correct_answer' => 'required|string',
                ]);

                $answersText = "
Question 1:
- Question: {$request->question}
- User's Answer: {$request->user_answer}
- Correct Answer: {$request->correct_answer}
";

                $prompt = "You are an expert educational AI that analyzes student performance.

Wrong Answers Analysis:
{$answersText}

Task: Analyze this wrong answer and provide a brief assessment.

Respond in pure JSON:
{
  \"overall_performance\": \"Brief assessment\",
  \"weak_areas\": [\"Area\"],
  \"recommendations\": [{\"topic\": \"Topic\", \"reason\": \"Reason\"}],
  \"study_plan\": \"Brief plan\"
}";

                $apiKey = env('GEMINI_API_KEY');
                $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

                $response = \Illuminate\Support\Facades\Http::timeout(30)->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post($baseUrl . '?key=' . $apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1000,
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $textResponse = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response';
                    $jsonResponse = json_decode($textResponse, true);

                    return response()->json([
                        'success' => true,
                        'data' => $jsonResponse ?: ['raw_response' => $textResponse]
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'API Error',
                        'status' => $response->status(),
                        'body' => $response->body()
                    ], 500);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        });
    });
    Route::group(["prefix" => "guest"], function(){
        Route::post("/login", [AuthController::class, "login"]);
        Route::post("/register", [AuthController::class, "register"]);
    });
    
    // Simple test route
    Route::get('/test', function () {
        return response()->json(['message' => 'API is working!']);
    });

    // Debug Gemini route
    Route::get('/debug-gemini', function () {
        try {
            $apiKey = env('GEMINI_API_KEY');
            $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

            $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '?key=' . $apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Say hello']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'maxOutputTokens' => 50,
                ]
            ]);

            return response()->json([
                'status' => $response->status(),
                'successful' => $response->successful(),
                'body' => $response->body()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    });

    // Test controller route
    Route::get('/test-controller', function () {
        return response()->json(['message' => 'Route works']);
    });
});