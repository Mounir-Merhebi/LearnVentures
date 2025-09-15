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
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        try {
            // Check if user is asking about available lessons/topics
            if ($this->isAskingForAvailableLessons($userMessage)) {
                return $this->handleLessonsQuery($session);
            }

            // Generate embedding for user query
            $queryEmbeddings = $this->embeddingService->embedTexts([$userMessage]);
            $queryVector = $queryEmbeddings[0] ?? [];

            if (empty($queryVector)) {
                return $this->generateOutOfScopeResponse($sessionId);
            }

            // Find candidate chunks from this grade
            $candidateEmbeddings = $this->getCandidateEmbeddings($session->grade_id);

            if ($candidateEmbeddings->isEmpty()) {
                // No embeddings exist for this grade, but check if the question is about
                // a known curriculum topic from the lessons database
                $isCurriculumTopic = $this->isCurriculumTopic($userMessage, $session->grade_id);

                if ($isCurriculumTopic) {
                    // Provide general explanation for curriculum topics even without embeddings
                    $aiResponse = $this->generateCurriculumExplanation($userMessage, $session->grade->name);

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
                } else {
                    // Not a curriculum topic - show knowledge base setup message
                    $aiResponse = "Hey there! 👋 I'm still getting my knowledge base ready for {$session->grade->name}. Once everything's set up, I'll be able to help you with questions about your lessons and chapters. In the meantime, feel free to ask me about general study tips or math concepts - I'm here to help you learn! 📚✨";

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
                // No relevant chunks found for this grade. The chatbot should only
                // answer questions related to the student's enrolled grade content.
                // Since no relevant content was found, respond in a friendly way that
                // guides them toward appropriate topics.
                $aiResponse = "Hey! 👋 I love helping with {$session->grade->name} topics! This question seems to be outside what we're covering in your current grade's lessons and chapters. 

Could you try asking about specific topics from your math curriculum? For example:
• Questions about equations or functions
• Problems from your current chapter
• Concepts you're learning in class

I'm here to help you understand your grade-level math better! What topic from your lessons would you like to explore? 🤔📝";

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
        if (empty(trim($context))) {
            // No context available - this should not happen in normal operation since we check for embeddings first
            $prompt = "You are a friendly educational AI assistant that only answers questions related to specific grade-level curriculum content. The user is asking: '{$query}'\n\nIMPORTANT: You should only provide answers based on the actual curriculum content for their grade. Since no relevant content was found for this question, respond with: 'I'm sorry, but this question is outside the scope of the curriculum content available for your current grade. I can only provide answers based on the lessons and chapters from your enrolled grade.'";
        } else {
            $prompt = "You are a friendly and encouraging educational AI assistant that helps students learn math concepts from their grade-level curriculum. You have access to specific lesson content and can also use your general knowledge to provide helpful explanations.

Context from grade curriculum lessons:
{$context}

Student's question: {$query}

IMPORTANT GUIDELINES:
- FIRST, check if the question is related to math concepts typically covered in the grade's curriculum
- If the question is DIRECTLY covered in the provided context, use that information primarily
- If the question is related to curriculum topics but not exactly in the context, you can use your general knowledge to explain the concept, but ALWAYS acknowledge that you're drawing from general mathematical knowledge
- If the question is completely unrelated to math or the grade's curriculum, politely redirect them to appropriate topics
- Be friendly, encouraging, and student-focused in your responses
- Include examples and explanations that help students understand
- Suggest related topics from their curriculum when appropriate

RESPONSE STYLE:
- Start with encouragement or acknowledgment
- Explain concepts clearly with examples
- Connect back to their curriculum when possible
- End with questions or suggestions to continue learning
- Use friendly language appropriate for students

If the topic is completely outside math curriculum: 'Hey! 👋 I'd love to help you learn, but this topic isn't part of your current math curriculum. Let's focus on the math concepts from your grade - what topic from your lessons would you like to explore?'";
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
     * Check if user is asking about available lessons/topics
     */
    private function isAskingForAvailableLessons(string $message): bool
    {
        $lowerMessage = strtolower($message);

        $lessonKeywords = [
            'what lessons', 'available lessons', 'what topics', 'available topics',
            'what do you know', 'what can you help with', 'what subjects',
            'what chapters', 'lesson list', 'topic list', 'what are the lessons',
            'what are the topics', 'what do you cover', 'what can you teach',
            'what math topics', 'what algebra topics', 'what geometry topics'
        ];

        foreach ($lessonKeywords as $keyword) {
            if (strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle queries about available lessons/topics
     */
    private function handleLessonsQuery($session): \Illuminate\Http\JsonResponse
    {
        try {
            // Get all lessons for this grade - simplified query to debug
            Log::info('Debug: Starting lessons query', [
                'session_grade_id' => $session->grade_id,
                'session_id' => $session->id
            ]);

            // First check if grade exists and has subjects
            $grade = \App\Models\Grade::find($session->grade_id);
            if (!$grade) {
                Log::error('Grade not found', ['grade_id' => $session->grade_id]);
                throw new \Exception('Grade not found');
            }

            $subjects = \App\Models\Subject::where('grade_id', $session->grade_id)->get();
            Log::info('Found subjects for grade', [
                'grade_id' => $session->grade_id,
                'subjects_count' => $subjects->count()
            ]);

            // Get lessons through the relationship chain
            $lessons = collect();
            foreach ($subjects as $subject) {
                $chapters = \App\Models\Chapter::where('subject_id', $subject->id)->get();
                foreach ($chapters as $chapter) {
                    $chapterLessons = \App\Models\Lesson::where('chapter_id', $chapter->id)->get();
                    $lessons = $lessons->merge($chapterLessons);
                }
            }

            Log::info('Lessons query result', [
                'grade_id' => $session->grade_id,
                'grade_name' => $grade->name,
                'subjects_count' => $subjects->count(),
                'lessons_count' => $lessons->count(),
                'lessons' => $lessons->pluck('title')->toArray()
            ]);

            if ($lessons->isEmpty()) {
                $response = "Hmm, it looks like the lessons for {$grade->name} are still being set up! 📚 I'll have more topics to help you with soon. In the meantime, I can help with general math concepts and study tips! 😊";
            } else {
                $response = "Awesome! 👋 Here are the topics and lessons available in {$grade->name} that I can help you with:\n\n";

                // Group by chapters using the nested relationship
                $chapters = [];
                foreach ($lessons as $lesson) {
                    // Get chapter info from the nested relationship
                    $chapter = \App\Models\Chapter::find($lesson->chapter_id);
                    $chapterTitle = $chapter ? $chapter->title : 'General Topics';

                    if (!isset($chapters[$chapterTitle])) {
                        $chapters[$chapterTitle] = [];
                    }
                    $chapters[$chapterTitle][] = $lesson->title;
                }

                foreach ($chapters as $chapterTitle => $chapterLessons) {
                    $response .= "**{$chapterTitle}**\n";
                    foreach ($chapterLessons as $lessonTitle) {
                        $response .= "• {$lessonTitle}\n";
                    }
                    $response .= "\n";
                }

                $response .= "Feel free to ask me questions about any of these topics! I can explain concepts, help with problems, and give you examples. What would you like to learn about? 🤔✨";
            }

            $aiMessage = ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $response,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'message_id' => $aiMessage->id,
                    'response' => $response,
                    'context_chunks_used' => 0,
                    'grade_scope' => $session->grade->name,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling lessons query: ' . $e->getMessage());

            $fallbackResponse = "I had trouble fetching the lesson list right now, but I can definitely help you with math topics from {$session->grade->name}! What specific area would you like to explore? 📝";

            $aiMessage = ChatMessage::create([
                'session_id' => $session->id,
                'role' => 'assistant',
                'content' => $fallbackResponse,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'message_id' => $aiMessage->id,
                    'response' => $fallbackResponse,
                    'context_chunks_used' => 0,
                    'grade_scope' => $session->grade->name,
                ]
            ]);
        }
    }

    /**
     * Check if the question is about a curriculum topic for this grade
     */
    private function isCurriculumTopic(string $message, int $gradeId): bool
    {
        $lowerMessage = strtolower($message);

        try {
            // Get all lessons for this grade
            $subjects = \App\Models\Subject::where('grade_id', $gradeId)->get();
            $lessons = collect();

            foreach ($subjects as $subject) {
                $chapters = \App\Models\Chapter::where('subject_id', $subject->id)->get();
                foreach ($chapters as $chapter) {
                    $chapterLessons = \App\Models\Lesson::where('chapter_id', $chapter->id)->get();
                    $lessons = $lessons->merge($chapterLessons);
                }
            }

            // Check if message contains any lesson titles or related keywords
            foreach ($lessons as $lesson) {
                $lessonTitle = strtolower($lesson->title);
                $conceptSlug = strtolower($lesson->concept_slug ?? '');

                // Check for exact lesson title matches
                if (strpos($lowerMessage, $lessonTitle) !== false) {
                    return true;
                }

                // Check for concept-related keywords
                $keywords = explode('-', $conceptSlug);
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) > 2 && strpos($lowerMessage, $keyword) !== false) {
                        return true;
                    }
                }
            }

            // Check for common math topic keywords that might be curriculum-related
            $mathTopics = [
                'linear equation', 'quadratic', 'algebra', 'function', 'graph',
                'equation', 'solve', 'variable', 'polynomial', 'fraction',
                'decimal', 'percentage', 'ratio', 'geometry', 'triangle',
                'circle', 'area', 'volume', 'calculus', 'derivative'
            ];

            foreach ($mathTopics as $topic) {
                if (strpos($lowerMessage, $topic) !== false) {
                    return true;
                }
            }

        } catch (\Exception $e) {
            Log::error('Error checking curriculum topic: ' . $e->getMessage());
            return false;
        }

        return false;
    }

    /**
     * Generate a general explanation for curriculum topics
     */
    private function generateCurriculumExplanation(string $message, string $gradeName): string
    {
        $prompt = "You are a friendly and encouraging math tutor helping a {$gradeName} student. The student asked: '{$message}'

This is a curriculum-related topic for their grade. Provide a clear, educational explanation that:
- Uses simple, student-friendly language
- Includes relevant examples
- Connects to what they might be learning in class
- Encourages further questions
- Keeps the response helpful and engaging

Remember: You're explaining math concepts that are appropriate for {$gradeName} level students. Be encouraging and make the student feel confident about learning!";

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
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1024,
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    $generatedText = trim($data['candidates'][0]['content']['parts'][0]['text']);

                    // Clean up the response
                    $generatedText = trim($generatedText);

                    return $generatedText;
                }
            }

            // Fallback response if API fails
            return "I'd be happy to help you understand that topic! 📚 While I'm still setting up detailed explanations for {$gradeName}, here's a quick overview: [This topic is part of your curriculum and involves key mathematical concepts you'll use throughout the course.]

Would you like me to explain any specific part of this topic, or shall I suggest some practice problems to help you understand it better? 🤔";

        } catch (\Exception $e) {
            Log::error('Curriculum explanation generation failed: ' . $e->getMessage());

            // Very basic fallback
            return "That's a great topic from your {$gradeName} curriculum! 📖 I'd love to help you understand it better. While I'm getting everything set up, could you tell me what specific part of this topic you'd like explained? I'm here to help you learn! 😊";
        }
    }

    /**
     * Estimate token count (rough approximation)
     */
    private function estimateTokenCount(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
