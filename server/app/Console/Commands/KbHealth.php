<?php

namespace App\Console\Commands;

use App\Models\KbChunk;
use App\Models\KbEmbedding;
use App\Models\Lesson;
use Illuminate\Console\Command;

class KbHealth extends Command
{
    protected $signature = 'kb:health';
    protected $description = 'Report health statistics for the knowledge base';

    public function handle()
    {
        $this->info('Knowledge Base Health Report');
        $this->line('================================');

        // Get counts
        $lessonsCount = Lesson::count();
        $chunksCount = KbChunk::count();
        $embeddingsCount = KbEmbedding::count();

        // Get embeddings by model
        $embeddingsByModel = KbEmbedding::select('model_name')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('model_name')
            ->get();

        // Get lessons without chunks
        $lessonsWithoutChunks = Lesson::whereDoesntHave('kbChunks')->count();

        // Get chunks without embeddings
        $chunksWithoutEmbeddings = KbChunk::whereDoesntHave('embeddings')->count();

        // Display statistics
        $this->line("📚 Total Lessons: {$lessonsCount}");
        $this->line("📄 Total Chunks: {$chunksCount}");
        $this->line("🧠 Total Embeddings: {$embeddingsCount}");
        $this->newLine();

        if ($embeddingsByModel->isNotEmpty()) {
            $this->line('Embeddings by Model:');
            foreach ($embeddingsByModel as $model) {
                $this->line("  • {$model->model_name}: {$model->count}");
            }
            $this->newLine();
        }

        // Health checks
        $this->line('Health Checks:');
        $this->line("  • Lessons without chunks: {$lessonsWithoutChunks}");

        if ($lessonsWithoutChunks > 0) {
            $this->warn("⚠️  {$lessonsWithoutChunks} lessons have no chunks - run kb:reindex-grade");
        } else {
            $this->info("✅ All lessons have chunks");
        }

        $this->line("  • Chunks without embeddings: {$chunksWithoutEmbeddings}");

        if ($chunksWithoutEmbeddings > 0) {
            $this->warn("⚠️  {$chunksWithoutEmbeddings} chunks have no embeddings - check queue processing");
        } else {
            $this->info("✅ All chunks have embeddings");
        }

        // Coverage percentage
        if ($lessonsCount > 0) {
            $coverage = round(($chunksCount / $lessonsCount) * 100, 2);
            $this->line("  • Average chunks per lesson: " . round($chunksCount / $lessonsCount, 2));
        }

        if ($chunksCount > 0) {
            $embeddingCoverage = round(($embeddingsCount / $chunksCount) * 100, 2);
            $this->line("  • Embedding coverage: {$embeddingCoverage}%");

            if ($embeddingCoverage < 100) {
                $this->warn("⚠️  Not all chunks have embeddings");
            } else {
                $this->info("✅ All chunks have embeddings");
            }
        }

        $this->newLine();
        $this->info('Health report completed!');

        return Command::SUCCESS;
    }
}
