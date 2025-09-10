<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\KbChunk;
use App\Models\KbEmbedding;
use App\Models\StudentGradeEnrollment;
use App\Services\EmbeddingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function __construct(
        private EmbeddingService $embeddingService
    ) {}

    /**
     * Create a new chat session
     */
    public function createSession(Request $request)
    {
        $request->validate([
            'grade_id' => 'required|exists:grades,id',
        ]);

        $user = Auth::user();
        $gradeId = $request->grade_id;

        // Check if user is enrolled and accepted in this grade
        $enrollment = StudentGradeEnrollment::where('user_id', $user->id)
            ->where('grade_id', $gradeId)
            ->where('status', 'accepted')
            ->first();

        if (!$enrollment) {
            return response()->json([
                'success' => false,
                'message' => 'You are not enrolled in this grade or your enrollment is not accepted'
            ], 403);
        }

        // Create chat session
        $session = ChatSession::create([
            'user_id' => $user->id,
            'grade_id' => $gradeId,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Chat session created successfully',
            'data' => [
                'session_id' => $session->id,
                'grade_id' => $gradeId,
                'started_at' => $session->started_at,
            ]
        ], 201);
    }

    /**
     * Send a message and get AI response
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:chat_sessions,id',
            'message' => 'required|string|max:1000',
        ]);

        $user = Auth::user();
        $sessionId = $request->session_id;
        $userMessage = trim($request->message);

        // Validate session belongs to user and get session with grade
        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->with('grade')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Chat session not found or access denied'
            ], 404);
        }

        // Save user message
        $userChatMessage = ChatMessage::create([
            'session_id' => $sessionId,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        try {
            // Generate embedding for user query
            $queryEmbeddings = $this->embeddingService->embedTexts([$userMessage]);
            $queryVector = $queryEmbeddings[0] ?? [];

            if (empty($queryVector)) {
                return $this->generateOutOfScopeResponse($sessionId);
            }

            // Find candidate chunks from this grade
            $candidateEmbeddings = $this->getCandidateEmbeddings($session->grade_id);

            if ($candidateEmbeddings->isEmpty()) {
                return $this->generateOutOfScopeResponse($sessionId);
            }

            // Find top-K similar chunks
            $topK = (int) env('RETRIEVAL_TOP_K', 8);
            $similarChunks = $this->embeddingService->findSimilarChunks(
                $queryVector,
                $candidateEmbeddings->toArray(),
                $topK
            );

            // Filter by similarity threshold
            $simThreshold = (float) env('SIM_THRESHOLD', 0.35);
            $relevantChunks = array_filter($similarChunks, function($chunk) use ($simThreshold) {
                return $chunk['similarity'] >= $simThreshold;
            });

            if (empty($relevantChunks)) {
                return $this->generateOutOfScopeResponse($sessionId);
            }

            // Build context window
            $context = $this->buildContextWindow($relevantChunks);

            // Generate AI response
            $aiResponse = $this->generateAIResponse($userMessage, $context);

            // Save AI response
            $aiChatMessage = ChatMessage::create([
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $aiResponse,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'message_id' => $aiChatMessage->id,
                    'response' => $aiResponse,
                    'context_chunks_used' => count($relevantChunks),
                    'grade_scope' => $session->grade->name,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Chat processing error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            return $this->generateOutOfScopeResponse($sessionId);
        }
    }

    /**
     * Get candidate embeddings filtered by grade
     */
    private function getCandidateEmbeddings($gradeId)
    {
        return KbEmbedding::join('kb_chunks', 'kb_embeddings.chunk_id', '=', 'kb_chunks.id')
            ->join('lessons', 'kb_chunks.lesson_id', '=', 'lessons.id')
            ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
            ->join('subjects', 'chapters.subject_id', '=', 'subjects.id')
            ->where('subjects.grade_id', $gradeId)
            ->where('kb_embeddings.model_name', env('EMBED_MODEL_NAME', 'local-embedding-model'))
            ->select('kb_embeddings.*')
            ->get();
    }

    /**
     * Build context window from relevant chunks
     */
    private function buildContextWindow(array $relevantChunks): string
    {
        $contextParts = [];
        $maxTokens = 2000; // Conservative limit
        $currentTokens = 0;

        foreach ($relevantChunks as $chunkData) {
            $chunk = $chunkData['embedding'];
            $chunkTokens = $this->estimateTokenCount($chunk->chunk->text);

            if ($currentTokens + $chunkTokens > $maxTokens) {
                break;
            }

            $contextParts[] = $chunk->chunk->text;
            $currentTokens += $chunkTokens;
        }

        return implode("\n\n", $contextParts);
    }

    /**
     * Generate AI response using context
     */
    private function generateAIResponse(string $query, string $context): string
    {
        $prompt = "You are a helpful educational AI assistant. Answer questions based ONLY on the provided context. If the question cannot be answered from the context, respond with: 'Out of scope for this grade.'

Context:
{$context}

Question: {$query}

Instructions:
- Answer based only on the provided context
- Be helpful and educational
- If the question is out of scope, say 'Out of scope for this grade'
- Keep answers concise but informative";

        // For now, return a mock response based on context availability
        if (empty($context)) {
            return "Out of scope for this grade.";
        }

        // Mock AI response - in production, call your LLM here
        return "Based on the lesson content, here's what I can tell you about your question: [AI would generate response based on context here].";
    }

    /**
     * Generate out-of-scope response
     */
    private function generateOutOfScopeResponse($sessionId): \Illuminate\Http\JsonResponse
    {
        $aiMessage = ChatMessage::create([
            'session_id' => $sessionId,
            'role' => 'assistant',
            'content' => 'Out of scope for this grade.',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'message_id' => $aiMessage->id,
                'response' => 'Out of scope for this grade.',
                'context_chunks_used' => 0,
            ]
        ]);
    }

    /**
     * Estimate token count (rough approximation)
     */
    private function estimateTokenCount(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
