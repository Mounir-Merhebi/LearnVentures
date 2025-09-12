<?php

namespace App\Services;

use App\Models\KbEmbedding;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    private string $modelName;
    private int $dimensions;
    private int $batchSize;
    private string $apiKey;
    private string $embeddingModel;

    public function __construct()
    {
        $this->modelName = env('EMBED_MODEL_NAME', 'gemini-embedding-model');
        $this->dimensions = (int) env('EMBED_DIM', 768); // Gemini embeddings are 768 dimensions
        $this->batchSize = (int) env('EMBED_BATCH_SIZE', 64);
        $this->apiKey = env('GEMINI_API_KEY');
        $this->embeddingModel = env('GEMINI_EMBEDDING_MODEL', 'text-embedding-004');

        if (!$this->apiKey) {
            throw new \RuntimeException('GEMINI_API_KEY not set in environment variables');
        }
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
     * Embed a single batch of texts using Gemini API
     */
    private function embedBatch(array $texts): array
    {
        if (empty($texts)) {
            return [];
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->embeddingModel}:embedContent?key={$this->apiKey}";

            // Gemini embedding API expects individual requests for each text
            $embeddings = [];
            foreach ($texts as $text) {
                $response = Http::timeout(30)->post($url, [
                    'content' => [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ]
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if (isset($data['embedding']['values'])) {
                        $embeddings[] = $data['embedding']['values'];
                    } else {
                        Log::error('Invalid Gemini embedding response format', [
                            'response' => $data,
                            'text_preview' => substr($text, 0, 100)
                        ]);
                        // Fallback to zero vector if embedding fails
                        $embeddings[] = array_fill(0, $this->dimensions, 0.0);
                    }
                } else {
                    Log::error('Gemini embedding API error', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                        'text_preview' => substr($text, 0, 100)
                    ]);
                    // Fallback to zero vector if API fails
                    $embeddings[] = array_fill(0, $this->dimensions, 0.0);
                }

                // Small delay to avoid rate limits
                usleep(100000); // 100ms delay
            }

            return $embeddings;

        } catch (\Exception $e) {
            Log::error('Gemini embedding API call failed', [
                'error' => $e->getMessage(),
                'texts_count' => count($texts)
            ]);

            // Return zero vectors as fallback
            return array_fill(0, count($texts), array_fill(0, $this->dimensions, 0.0));
        }
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
