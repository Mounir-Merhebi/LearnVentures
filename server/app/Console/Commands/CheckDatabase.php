<?php

namespace App\Console\Commands;

use App\Models\Grade;
use App\Models\Lesson;
use App\Models\KbChunk;
use App\Models\KbEmbedding;
use Illuminate\Console\Command;

class CheckDatabase extends Command
{
    protected $signature = 'db:check';
    protected $description = 'Check database content for grades, lessons, and embeddings';

    public function handle()
    {
        $this->info('=== Database Content Check ===');
        $this->newLine();

        $this->info('Grades:');
        $grades = Grade::all();
        if ($grades->isEmpty()) {
            $this->warn('No grades found');
        } else {
            foreach ($grades as $grade) {
                $this->line("  ID: {$grade->id}, Name: {$grade->name}");
            }
        }
        $this->newLine();

        $this->info('Lessons:');
        $lessons = Lesson::all();
        if ($lessons->isEmpty()) {
            $this->warn('No lessons found');
        } else {
            foreach ($lessons as $lesson) {
                $this->line("  ID: {$lesson->id}, Title: {$lesson->title}");
            }
        }
        $this->newLine();

        $this->info('Knowledge Base:');
        $chunks = KbChunk::count();
        $embeddings = KbEmbedding::count();
        $this->line("  Chunks: {$chunks}");
        $this->line("  Embeddings: {$embeddings}");

        if ($embeddings > 0) {
            $this->info('✓ Knowledge base has embeddings - ready for RAG!');
        } else {
            $this->warn('No embeddings found - need to run reindexing');
        }

        return Command::SUCCESS;
    }
}
