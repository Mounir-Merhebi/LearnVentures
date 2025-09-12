<?php

namespace App\Console\Commands;

use App\Jobs\EmbedChunksJob;
use App\Models\Grade;
use App\Models\KbChunk;
use App\Models\Lesson;
use App\Services\ChunkerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KbReindexGrade extends Command
{
    protected $signature = 'kb:reindex-grade {gradeId : The ID of the grade to reindex}';
    protected $description = 'Reindex all lessons in a grade for knowledge base search';

    public function __construct(
        private ChunkerService $chunkerService
    ) {
        parent::__construct();
    }

    public function handle()
    {
        $gradeId = $this->argument('gradeId');

        // Validate grade exists
        $grade = Grade::find($gradeId);
        if (!$grade) {
            $this->error("Grade with ID {$gradeId} not found");
            return Command::FAILURE;
        }

        $this->info("Starting reindex for grade: {$grade->name} (ID: {$gradeId})");

        // Get all lessons in this grade via joins
        $lessons = Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
            ->join('subjects', 'chapters.subject_id', '=', 'subjects.id')
            ->where('subjects.grade_id', $gradeId)
            ->select('lessons.*')
            ->get();

        if ($lessons->isEmpty()) {
            $this->warn("No lessons found for grade {$grade->name}");
            return Command::SUCCESS;
        }

        $this->info("Found {$lessons->count()} lessons to process");

        $totalChunksCreated = 0;
        $chunkIdsToEmbed = [];

        $this->withProgressBar($lessons, function ($lesson) use (&$totalChunksCreated, &$chunkIdsToEmbed) {
            $chunksCreated = $this->processLesson($lesson);
            $totalChunksCreated += $chunksCreated['count'];
            $chunkIdsToEmbed = array_merge($chunkIdsToEmbed, $chunksCreated['chunk_ids']);
        });

        $this->newLine();
        $this->info("Created {$totalChunksCreated} chunks");

        if (!empty($chunkIdsToEmbed)) {
            $this->info("Dispatching embedding job for " . count($chunkIdsToEmbed) . " chunks");
            EmbedChunksJob::dispatch($chunkIdsToEmbed);
            $this->info("Embedding job dispatched successfully");
        }

        Log::info('Grade reindexing completed', [
            'grade_id' => $gradeId,
            'lessons_processed' => $lessons->count(),
            'chunks_created' => $totalChunksCreated
        ]);

        return Command::SUCCESS;
    }

    private function processLesson(Lesson $lesson): array
    {
        $chunksCreated = 0;
        $chunkIds = [];

        // Check if content has changed
        $existingChunks = KbChunk::where('lesson_id', $lesson->id)->get();
        $hasExistingChunks = $existingChunks->isNotEmpty();

        if ($hasExistingChunks) {
            // Check if content hash has changed
            $existingHash = $existingChunks->first()->content_hash;
            if (!$this->chunkerService->hasContentChanged($lesson->content, $existingHash)) {
                $this->warn("Lesson {$lesson->id} content unchanged, skipping");
                return ['count' => 0, 'chunk_ids' => []];
            }

            // Delete existing chunks and embeddings
            KbChunk::where('lesson_id', $lesson->id)->delete();
            $this->info("Deleted existing chunks for lesson {$lesson->id}");
        }

        // Create new chunks (use lower minimum for smaller lessons)
        $chunks = $this->chunkerService->chunkContent($lesson->content, $lesson->version, 100, 500, 0.15);

        foreach ($chunks as $chunkData) {
            $chunk = KbChunk::create([
                'lesson_id' => $lesson->id,
                'chunk_index' => $chunkData['chunk_index'],
                'text' => $chunkData['text'],
                'source_lesson_version' => $chunkData['source_lesson_version'],
                'content_hash' => $chunkData['content_hash'],
            ]);

            $chunkIds[] = $chunk->id;
            $chunksCreated++;
        }

        return ['count' => $chunksCreated, 'chunk_ids' => $chunkIds];
    }
}
