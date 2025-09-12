<?php

namespace App\Console\Commands;

use App\Services\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestGeminiIntegration extends Command
{
    protected $signature = 'test:gemini-integration';
    protected $description = 'Test Gemini API integration for embeddings and chat';

    public function handle()
    {
        $this->info('=== Testing Gemini Integration ===');
        $this->newLine();

        // Check configuration
        $this->info('Configuration:');
        $this->line('GEMINI_API_KEY: ' . (env('GEMINI_API_KEY') ? 'SET' : 'NOT SET'));
        $this->line('GEMINI_MODEL: ' . env('GEMINI_MODEL', 'gemini-1.5-flash'));
        $this->line('GEMINI_EMBEDDING_MODEL: ' . env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004'));
        $this->newLine();

        // Check API key
        if (!env('GEMINI_API_KEY')) {
            $this->error('GEMINI_API_KEY is not set. Please set it in your environment variables.');
            $this->info('You can get an API key from: https://makersuite.google.com/app/apikey');
            return Command::FAILURE;
        }

        // Test embedding service
        $this->info('Testing EmbeddingService:');
        try {
            $embeddingService = app(EmbeddingService::class);
            $this->info('✓ EmbeddingService instantiated successfully');

            // Test with a simple text
            $testTexts = ["What is the quadratic formula?"];
            $this->info('Generating embedding for test text...');
            $embeddings = $embeddingService->embedTexts($testTexts);

            if (!empty($embeddings) && isset($embeddings[0])) {
                $this->info('✓ Embedding generated successfully');
                $this->line('  Embedding dimensions: ' . count($embeddings[0]));
            } else {
                $this->error('✗ No embeddings generated');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('✗ EmbeddingService error: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Test chat generation
        $this->newLine();
        $this->info('Testing Chat Generation:');
        try {
            $apiKey = env('GEMINI_API_KEY');
            $model = env('GEMINI_MODEL', 'gemini-1.5-flash');

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $response = Http::timeout(30)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Hello, can you explain what a quadratic equation is?']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 256,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];
                    $this->info('✓ Chat response generated successfully');
                    $this->line('  Response preview: ' . substr($generatedText, 0, 100) . '...');
                } else {
                    $this->error('✗ Invalid chat response format');
                }
            } else {
                $this->error('✗ Chat API error: ' . $response->status() . ' - ' . $response->body());
            }
        } catch (\Exception $e) {
            $this->error('✗ Chat test error: ' . $e->getMessage());
        }

        $this->newLine();
        $this->info('=== Test Complete ===');

        return Command::SUCCESS;
    }
}
