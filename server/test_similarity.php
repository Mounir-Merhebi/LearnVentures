<?php

require 'vendor/autoload.php';
require 'bootstrap/app.php';

echo "=== Testing Similarity Calculation ===\n\n";

// Get the stored embedding
$embedding = \App\Models\KbEmbedding::first();
if (!$embedding) {
    echo "No embeddings found in database!\n";
    exit(1);
}

echo "Found embedding for chunk ID: {$embedding->chunk_id}\n";
echo "Embedding model: {$embedding->model_name}\n";
echo "Vector dimensions: " . count($embedding->vector) . "\n\n";

// Test query embedding generation
$embeddingService = app(\App\Services\EmbeddingService::class);
$queryText = "What is the quadratic formula?";
$queryEmbedding = $embeddingService->embedTexts([$queryText])[0];

echo "Query: '$queryText'\n";
echo "Query embedding dimensions: " . count($queryEmbedding) . "\n\n";

// Calculate similarity
$similarity = \App\Services\EmbeddingService::cosineSimilarity($queryEmbedding, $embedding->vector);
echo "Cosine similarity: " . number_format($similarity, 4) . "\n";

// Check against threshold
$threshold = env('SIM_THRESHOLD', 0.35);
echo "Similarity threshold: $threshold\n";
echo "Above threshold: " . ($similarity >= $threshold ? 'YES' : 'NO') . "\n\n";

// Test with different query
$queryText2 = "math quadratic equation formula";
$queryEmbedding2 = $embeddingService->embedTexts([$queryText2])[0];
$similarity2 = \App\Services\EmbeddingService::cosineSimilarity($queryEmbedding2, $embedding->vector);

echo "Query 2: '$queryText2'\n";
echo "Similarity 2: " . number_format($similarity2, 4) . "\n";
echo "Above threshold: " . ($similarity2 >= $threshold ? 'YES' : 'NO') . "\n\n";

// Show chunk text preview
$chunk = $embedding->chunk;
echo "Chunk text preview: " . substr($chunk->text, 0, 200) . "...\n";

echo "\n=== Similarity Test Complete ===\n";
