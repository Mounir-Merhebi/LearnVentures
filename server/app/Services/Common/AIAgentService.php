<?php

namespace App\Services\Common;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAgentService
{
    private $baseUrl;
    private $token;

    public function __construct()
    {
        $this->baseUrl = env('AI_AGENT_BASE_URL', 'http://localhost:5000');
        $this->token = env('AI_AGENT_SHARED_TOKEN', 'finalproject');
    }

    /**
     * Analyze student performance based on wrong answers
     */
    public function analyzePerformance(array $wrongAnswers)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/analyze-performance', $wrongAnswers);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('AI Agent analyze performance failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'AI analysis failed',
                'details' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('AI Agent communication error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Communication error with AI agent'
            ];
        }
    }

    /**
     * Get personalized lesson based on user preferences
     */
    public function personalizeLesson(array $preferences, string $lessonText)
    {
        try {
            $payload = [
                'preferences' => $preferences,
                'lesson' => ['lesson_text' => $lessonText]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/personalize-lesson', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            Log::error('AI Agent personalize lesson failed', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'error' => 'Lesson personalization failed',
                'details' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('AI Agent communication error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Communication error with AI agent'
            ];
        }
    }

    /**
     * Check if AI agent is healthy
     */
    public function healthCheck()
    {
        try {
            $response = Http::get($this->baseUrl . '/health');
            return $response->successful() && $response->json()['ok'] === true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
