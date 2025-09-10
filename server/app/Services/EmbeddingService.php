<?php

namespace App\Services;

use App\Models\KbEmbedding;
use Illuminate\Support\Facades\Log;

class EmbeddingService
{
    private string $modelName;
    private int $dimensions;
    private int $batchSize;

    public function __construct()
    {
        $this->modelName = env('EMBED_MODEL_NAME', 'local-embedding-model');
        $this->dimensions = (int) env('EMBED_DIM', 384);
        $this->batchSize = (int) env('EMBED_BATCH_SIZE', 64);
    }

    /**
     * Generate embeddings for an array of texts
     *
     * @param array $texts Array of strings to embed
     * @return array Array of embedding vectors
     */
    public function embedTexts(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        $embeddings = [];

        // Process in batches to avoid memory issues
        $batches = array_chunk($texts, $this->batchSize);

        foreach ($batches as $batch) {
            $batchEmbeddings = $this->embedBatch($batch);
            $embeddings = array_merge($embeddings, $batchEmbeddings);
        }

        Log::info('Embeddings generated successfully', [
            'total_texts' => count($texts),
            'batches_processed' => count($batches),
            'model_name' => $this->modelName
        ]);

        return $embeddings;
    }

    /**
     * Embed a single batch of texts
     */
    private function embedBatch(array $texts): array
    {
        // This is a placeholder for the actual local embedding model call
        // In a real implementation, you would call your local embedding service here
        // For now, we'll generate random vectors for demonstration

        $embeddings = [];
        foreach ($texts as $text) {
            $embeddings[] = $this->generateMockEmbedding($text);
        }

        return $embeddings;
    }

    /**
     * Generate a mock embedding vector (replace with actual model call)
     */
    private function generateMockEmbedding(string $text): array
    {
        // Generate a deterministic but random-looking vector based on text hash
        // This is just for demonstration - replace with actual embedding model call
        $hash = hash('sha256', $text);
        $vector = [];

        for ($i = 0; $i < $this->dimensions; $i++) {
            $hashPart = substr($hash, $i % 64, 2);
            $value = hexdec($hashPart) / 255.0; // Normalize to 0-1
            $vector[] = $value * 2 - 1; // Convert to -1 to 1 range
        }

        return $vector;
    }

    /**
     * Store embeddings in database with upsert to handle duplicates
     *
     * @param array $embeddings Array of ['chunk_id' => int, 'vector' => array]
     */
    public function storeEmbeddings(array $embeddings): void
    {
        if (empty($embeddings)) {
            return;
        }

        $records = [];
        foreach ($embeddings as $embedding) {
            $records[] = [
                'chunk_id' => $embedding['chunk_id'],
                'model_name' => $this->modelName,
                'dim' => $this->dimensions,
                'vector' => json_encode($embedding['vector']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Use upsert to handle duplicates based on chunk_id and model_name
        KbEmbedding::upsert($records, ['chunk_id', 'model_name'], ['vector', 'updated_at']);

        Log::info('Embeddings stored successfully', [
            'total_stored' => count($records),
            'model_name' => $this->modelName
        ]);
    }

    /**
     * Calculate cosine similarity between two vectors
     */
    public static function cosineSimilarity(array $vectorA, array $vectorB): float
    {
        if (count($vectorA) !== count($vectorB)) {
            throw new \InvalidArgumentException('Vectors must have the same dimensions');
        }

        $dotProduct = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($vectorA); $i++) {
            $dotProduct += $vectorA[$i] * $vectorB[$i];
            $normA += $vectorA[$i] * $vectorA[$i];
            $normB += $vectorB[$i] * $vectorB[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / ($normA * $normB);
    }

    /**
     * Find most similar chunks using cosine similarity
     *
     * @param array $queryVector The query embedding vector
     * @param array $candidateEmbeddings Array of candidate embeddings
     * @param int $topK Number of top results to return
     * @return array Array of ['embedding' => KbEmbedding, 'similarity' => float]
     */
    public function findSimilarChunks(array $queryVector, array $candidateEmbeddings, int $topK = 8): array
    {
        $similarities = [];

        foreach ($candidateEmbeddings as $embedding) {
            $similarity = self::cosineSimilarity($queryVector, $embedding->vector);
            $similarities[] = [
                'embedding' => $embedding,
                'similarity' => $similarity
            ];
        }

        // Sort by similarity descending
        usort($similarities, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice($similarities, 0, $topK);
    }
}
