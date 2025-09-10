<?php

namespace App\Jobs;

use App\Models\KbChunk;
use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmbedChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $chunkIds;
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(array $chunkIds)
    {
        $this->chunkIds = $chunkIds;
    }

    /**
     * Execute the job.
     */
    public function handle(EmbeddingService $embeddingService): void
    {
        Log::info('Starting EmbedChunksJob', [
            'chunk_count' => count($this->chunkIds),
            'chunk_ids' => $this->chunkIds
        ]);

        // Fetch chunks that don't have embeddings yet
        $modelName = env('EMBED_MODEL_NAME', 'local-embedding-model');
        $chunks = KbChunk::whereIn('id', $this->chunkIds)
            ->whereDoesntHave('embeddings', function ($query) use ($modelName) {
                $query->where('model_name', $modelName);
            })
            ->get();

        if ($chunks->isEmpty()) {
            Log::info('No chunks need embedding', ['chunk_ids' => $this->chunkIds]);
            return;
        }

        // Extract texts for embedding
        $texts = $chunks->pluck('text')->toArray();

        // Generate embeddings
        $embeddings = $embeddingService->embedTexts($texts);

        // Prepare data for storage
        $embeddingData = [];
        foreach ($chunks as $index => $chunk) {
            if (isset($embeddings[$index])) {
                $embeddingData[] = [
                    'chunk_id' => $chunk->id,
                    'vector' => $embeddings[$index]
                ];
            }
        }

        // Store embeddings
        $embeddingService->storeEmbeddings($embeddingData);

        Log::info('EmbedChunksJob completed successfully', [
            'processed_chunks' => count($chunks),
            'generated_embeddings' => count($embeddingData)
        ]);
    }
}
