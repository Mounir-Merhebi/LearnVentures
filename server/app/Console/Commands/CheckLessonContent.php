<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use Illuminate\Console\Command;

class CheckLessonContent extends Command
{
    protected $signature = 'lesson:check {lessonId}';
    protected $description = 'Check the content of a specific lesson';

    public function handle()
    {
        $lessonId = $this->argument('lessonId');

        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            $this->error("Lesson {$lessonId} not found");
            return Command::FAILURE;
        }

        $this->info("Lesson: {$lesson->title}");
        $this->line("ID: {$lesson->id}");
        $this->line("Content Length: " . strlen($lesson->content) . " characters");

        if (empty(trim($lesson->content))) {
            $this->error("Lesson content is empty!");
            return Command::FAILURE;
        }

        $this->newLine();
        $this->info("Content Preview (first 500 characters):");
        $this->line(substr($lesson->content, 0, 500) . (strlen($lesson->content) > 500 ? '...' : ''));

        return Command::SUCCESS;
    }
}
