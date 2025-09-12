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
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    private string $apiKey;
    private string $model;

    public function __construct(
        private EmbeddingService $embeddingService
    ) {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_MODEL', 'gemini-1.5-flash');

        if (!$this->apiKey) {
            throw new \RuntimeException('GEMINI_API_KEY not set in environment variables');
        }
    }

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
                // No embeddings exist for this grade — instead of immediately returning
                // an out-of-scope message, generate a friendly, general response
                // (the assistant will greet the user and give a concise general answer)
                $aiResponse = $this->generateAIResponse($userMessage, '');

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
                        'context_chunks_used' => 0,
                        'grade_scope' => $session->grade->name,
                    ]
                ]);
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
                // No relevant chunks passed the similarity threshold. Provide a
                // helpful, polite general answer instead of an immediate
                // 'out of scope' reply so the bot feels conversational.
                $aiResponse = $this->generateAIResponse($userMessage, '');

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
                        'context_chunks_used' => 0,
                        'grade_scope' => $session->grade->name,
                    ]
                ]);
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
     * Generate AI response using Gemini API
     */
    private function generateAIResponse(string $query, string $context): string
    {
        // If no context is provided, we still want the assistant to be friendly
        // and give a useful, grade-appropriate reply. Build a more permissive
        // prompt in that case which still nudges the assistant to stay close
        // to the curriculum when possible.
        if (empty(trim($context))) {
            $prompt = "You are a friendly educational AI assistant specialized in the site's lesson content. Greet the user briefly. Before answering, determine whether the question is closely related to topics taught in the user's grade-level mathematics lessons.\n\nIf the question IS closely related to grade-level lesson topics, provide a concise, curriculum-appropriate answer framed for a learner at that grade.\n\nIf the question is NOT closely related to the grade's lesson material, do NOT fabricate or invent lesson-specific details. Instead reply: 'I don't have lesson material for that topic in this grade; I can give a brief high-level overview if you'd like.' You may offer to search lessons or suggest relevant keywords the user can ask about.\n\nKeep the reply polite, short, and helpful.\n\nQuestion: {$query}\n\nInstructions:\n- Greet the user briefly\n- Only provide lesson-style answers when the topic clearly matches grade-level curriculum topics\n- If not matched, give a short honest notice and offer a high-level overview or follow-up options\n- Do not say 'Out of scope' immediately or provide fabricated lesson content";
        } else {
            $prompt = "You are a helpful educational AI assistant. Answer questions based ONLY on the provided context. If the question cannot be answered from the context, respond with: 'Out of scope for this grade.'\n\nContext:\n{$context}\n\nQuestion: {$query}\n\nInstructions:\n- Answer based only on the provided context\n- Be helpful and educational\n- If the question is out of scope, say 'Out of scope for this grade'\n- Keep answers concise but informative";
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $response = Http::timeout(60)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 1024,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];

                    // Clean up the response
                    $generatedText = trim($generatedText);

                    // If Gemini returns something indicating it's out of scope, standardize it
                    if (stripos($generatedText, 'out of scope') !== false ||
                        stripos($generatedText, 'cannot be answered') !== false) {
                        return "Out of scope for this grade.";
                    }

                    return $generatedText;
                } else {
                    Log::error('Invalid Gemini response format for chat', [
                        'response' => $data,
                        'query_preview' => substr($query, 0, 100)
                    ]);
                    return "I'm sorry, I encountered an error while processing your question.";
                }
            } else {
                Log::error('Gemini API error for chat', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query_preview' => substr($query, 0, 100)
                ]);
                return "I'm sorry, I encountered an error while processing your question.";
            }

        } catch (\Exception $e) {
            Log::error('Gemini API call failed for chat', [
                'error' => $e->getMessage(),
                'query_preview' => substr($query, 0, 100)
            ]);
            return "I'm sorry, I encountered an error while processing your question.";
        }
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
