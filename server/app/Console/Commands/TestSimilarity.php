<?php

namespace App\Console\Commands;

use App\Models\KbEmbedding;
use App\Services\EmbeddingService;
use Illuminate\Console\Command;

class TestSimilarity extends Command
{
    protected $signature = 'test:similarity {query?}';
    protected $description = 'Test similarity calculation between queries and stored embeddings';

    public function handle()
    {
        $queryText = $this->argument('query') ?? 'What is the quadratic formula?';

        $this->info("=== Testing Similarity Calculation ===");
        $this->newLine();

        // Get stored embeddings
        $embeddings = KbEmbedding::with('chunk')->get();

        if ($embeddings->isEmpty()) {
            $this->error('No embeddings found in database!');
            return Command::FAILURE;
        }

        $this->info("Found {$embeddings->count()} embeddings");
        $this->newLine();

        // Test query embedding generation
        $embeddingService = app(EmbeddingService::class);
        $queryEmbedding = $embeddingService->embedTexts([$queryText])[0];

        $this->info("Query: '$queryText'");
        $this->line("Query embedding dimensions: " . count($queryEmbedding));

        $threshold = env('SIM_THRESHOLD', 0.35);
        $this->line("Similarity threshold: $threshold");
        $this->newLine();

        // Calculate similarity for each embedding
        $results = [];
        foreach ($embeddings as $embedding) {
            $similarity = EmbeddingService::cosineSimilarity($queryEmbedding, $embedding->vector);
            $results[] = [
                'chunk_id' => $embedding->chunk_id,
                'similarity' => $similarity,
                'above_threshold' => $similarity >= $threshold,
                'text_preview' => substr($embedding->chunk->text, 0, 100) . '...'
            ];
        }

        // Sort by similarity (highest first)
        usort($results, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        // Display results
        foreach ($results as $result) {
            $status = $result['above_threshold'] ? '✓' : '✗';
            $this->line("$status Chunk {$result['chunk_id']}: " . number_format($result['similarity'], 4));
            $this->line("    Text: {$result['text_preview']}");
            $this->newLine();
        }

        $relevantCount = count(array_filter($results, fn($r) => $r['above_threshold']));
        $this->info("Summary:");
        $this->line("  Total chunks: " . count($results));
        $this->line("  Relevant chunks (above threshold): $relevantCount");

        if ($relevantCount === 0) {
            $this->warn("⚠️  No chunks are above the similarity threshold!");
            $this->warn("   This explains why you're getting 'out of scope'");
            $this->line("   Try lowering SIM_THRESHOLD in .env (current: $threshold)");
            $this->line("   Or try a different query that's more similar to the content");
        } else {
            $this->info("✅ Found relevant chunks - should work in API!");
        }

        return Command::SUCCESS;
    }
}
