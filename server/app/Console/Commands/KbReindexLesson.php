<?php

namespace App\Console\Commands;

use App\Jobs\EmbedChunksJob;
use App\Models\KbChunk;
use App\Models\Lesson;
use App\Services\ChunkerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class KbReindexLesson extends Command
{
    protected $signature = 'kb:reindex-lesson {lessonId : The ID of the lesson to reindex}';
    protected $description = 'Reindex a specific lesson for knowledge base search';

    public function __construct(
        private ChunkerService $chunkerService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $lessonId = $this->argument('lessonId');

        // Validate lesson exists
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            $this->error("Lesson with ID {$lessonId} not found");
            return Command::FAILURE;
        }

        $this->info("Starting reindex for lesson: {$lesson->title} (ID: {$lessonId})");

        // Check if content has changed
        $existingChunks = KbChunk::where('lesson_id', $lessonId)->get();
        $hasExistingChunks = $existingChunks->isNotEmpty();

        if ($hasExistingChunks) {
            // Check if content hash has changed
            $existingHash = $existingChunks->first()->content_hash;
            if (!$this->chunkerService->hasContentChanged($lesson->content, $existingHash)) {
                $this->warn("Lesson {$lessonId} content unchanged, skipping");
                return Command::SUCCESS;
            }

            // Delete existing chunks and embeddings
            KbChunk::where('lesson_id', $lessonId)->delete();
            $this->info("Deleted existing chunks for lesson {$lessonId}");
        }

        // Create new chunks
        $chunks = $this->chunkerService->chunkContent($lesson->content, $lesson->version);

        if (empty($chunks)) {
            $this->warn("No chunks created for lesson {$lessonId}");
            return Command::SUCCESS;
        }

        $this->info("Creating " . count($chunks) . " chunks");
        $chunkIds = [];

        foreach ($chunks as $chunkData) {
            $chunk = KbChunk::create([
                'lesson_id' => $lessonId,
                'chunk_index' => $chunkData['chunk_index'],
                'text' => $chunkData['text'],
                'source_lesson_version' => $chunkData['source_lesson_version'],
                'content_hash' => $chunkData['content_hash'],
            ]);

            $chunkIds[] = $chunk->id;
        }

        $this->info("Created " . count($chunks) . " chunks");

        // Dispatch embedding job
        if (!empty($chunkIds)) {
            $this->info("Dispatching embedding job for " . count($chunkIds) . " chunks");
            EmbedChunksJob::dispatch($chunkIds);
            $this->info("Embedding job dispatched successfully");
        }

        Log::info('Lesson reindexing completed', [
            'lesson_id' => $lessonId,
            'chunks_created' => count($chunks)
        ]);

        return Command::SUCCESS;
    }
}
