<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

echo "=== Testing Gemini Integration ===\n\n";

// Check configuration
echo "Configuration:\n";
echo "GEMINI_API_KEY: " . (env('GEMINI_API_KEY') ? "SET" : "NOT SET") . "\n";
echo "GEMINI_MODEL: " . env('GEMINI_MODEL', 'gemini-1.5-flash') . "\n";
echo "GEMINI_EMBEDDING_MODEL: " . env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004') . "\n\n";

// Check database
echo "Database counts:\n";
echo "Lessons: " . \App\Models\Lesson::count() . "\n";
echo "Grades: " . \App\Models\Grade::count() . "\n";
echo "KbChunks: " . \App\Models\KbChunk::count() . "\n";
echo "KbEmbeddings: " . \App\Models\KbEmbedding::count() . "\n\n";

// Test embedding service
echo "Testing EmbeddingService:\n";
try {
    $embeddingService = app(\App\Services\EmbeddingService::class);
    echo "✓ EmbeddingService instantiated successfully\n";

    // Test with a simple text
    $testTexts = ["What is the quadratic formula?"];
    $embeddings = $embeddingService->embedTexts($testTexts);
    echo "✓ Generated embedding for test text\n";
    echo "  Embedding dimensions: " . count($embeddings[0] ?? []) . "\n";
} catch (\Exception $e) {
    echo "✗ EmbeddingService error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===";
