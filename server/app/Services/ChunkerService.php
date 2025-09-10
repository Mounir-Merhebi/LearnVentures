<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ChunkerService
{
    /**
     * Chunk lesson content into smaller pieces
     *
     * @param string $content The lesson content to chunk
     * @param int $lessonVersion The version of the lesson
     * @param int $minTokens Minimum tokens per chunk (default: 200)
     * @param int $maxTokens Maximum tokens per chunk (default: 500)
     * @param float $overlapPercentage Overlap percentage (default: 15%)
     * @return array Array of chunks with chunk_index, text, source_lesson_version, content_hash
     */
    public function chunkContent(
        string $content,
        int $lessonVersion,
        int $minTokens = 200,
        int $maxTokens = 500,
        float $overlapPercentage = 0.15
    ): array {
        if (empty(trim($content))) {
            return [];
        }

        // Split content into sentences for better boundary awareness
        $sentences = $this->splitIntoSentences($content);

        $chunks = [];
        $currentChunk = '';
        $currentTokenCount = 0;
        $chunkIndex = 0;
        $overlapTokens = (int)($maxTokens * $overlapPercentage);

        foreach ($sentences as $sentence) {
            $sentenceTokens = $this->estimateTokenCount($sentence);

            // If adding this sentence would exceed max tokens, finalize current chunk
            if ($currentTokenCount + $sentenceTokens > $maxTokens && !empty($currentChunk)) {
                if ($currentTokenCount >= $minTokens) {
                    $chunks[] = $this->createChunk($currentChunk, $chunkIndex++, $lessonVersion);
                    $currentChunk = '';
                    $currentTokenCount = 0;

                    // Add overlap from previous chunk
                    if ($overlapTokens > 0 && count($chunks) > 0) {
                        $overlapText = $this->getOverlapText($chunks[count($chunks) - 1]['text'], $overlapTokens);
                        $currentChunk = $overlapText;
                        $currentTokenCount = $this->estimateTokenCount($overlapText);
                    }
                }
            }

            $currentChunk .= $sentence;
            $currentTokenCount += $sentenceTokens;
        }

        // Add final chunk if it meets minimum requirements
        if (!empty($currentChunk) && $currentTokenCount >= $minTokens) {
            $chunks[] = $this->createChunk($currentChunk, $chunkIndex, $lessonVersion);
        }

        Log::info('Content chunked successfully', [
            'total_chunks' => count($chunks),
            'lesson_version' => $lessonVersion,
            'total_sentences' => count($sentences)
        ]);

        return $chunks;
    }

    /**
     * Split text into sentences
     */
    private function splitIntoSentences(string $text): array
    {
        // Split on sentence endings with lookbehind to preserve the punctuation
        $sentences = preg_split('/(?<=[.!?])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // Filter out empty sentences and trim whitespace
        return array_filter(array_map('trim', $sentences));
    }

    /**
     * Estimate token count (rough approximation: ~4 characters per token)
     */
    private function estimateTokenCount(string $text): int
    {
        return (int)ceil(strlen($text) / 4);
    }

    /**
     * Create a chunk array with required fields
     */
    private function createChunk(string $text, int $chunkIndex, int $lessonVersion): array
    {
        return [
            'chunk_index' => $chunkIndex,
            'text' => trim($text),
            'source_lesson_version' => $lessonVersion,
            'content_hash' => hash('sha256', trim($text))
        ];
    }

    /**
     * Get overlap text from the end of a chunk
     */
    private function getOverlapText(string $text, int $overlapTokens): string
    {
        $words = explode(' ', $text);
        $overlapText = '';
        $tokenCount = 0;

        for ($i = count($words) - 1; $i >= 0 && $tokenCount < $overlapTokens; $i--) {
            $overlapText = $words[$i] . ' ' . $overlapText;
            $tokenCount += $this->estimateTokenCount($words[$i]);
        }

        return trim($overlapText);
    }

    /**
     * Check if content has changed based on hash
     */
    public function hasContentChanged(string $newContent, ?string $existingHash): bool
    {
        if ($existingHash === null) {
            return true;
        }

        return hash('sha256', trim($newContent)) !== $existingHash;
    }
}
