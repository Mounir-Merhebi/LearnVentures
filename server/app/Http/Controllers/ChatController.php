<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\StudentGradeEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
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

        Log::info('Chat session created', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'grade_id' => $gradeId,
            'created_at' => $session->started_at
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

        Log::info('Chat message request', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'message_length' => strlen($request->message)
        ]);

        $userMessage = trim($request->message);

        // Validate session belongs to user and get session with grade
        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->with('grade')
            ->first();

        Log::info('Session lookup result', [
            'session_id' => $sessionId,
            'user_id' => $user->id,
            'session_found' => $session ? true : false,
            'session_data' => $session ? [
                'id' => $session->id,
                'user_id' => $session->user_id,
                'grade_id' => $session->grade_id
            ] : null
        ]);

        if (!$session) {
            Log::warning('Session not found or access denied', [
                'session_id' => $sessionId,
                'user_id' => $user->id
            ]);
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
            // Generate AI response using OpenAI API
            $aiResponse = $this->generateAIResponse($userMessage, $session->grade->name);

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
                    'session_id' => $sessionId,
                    'grade_scope' => $session->grade->name,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Chat processing error', [
                'session_id' => $sessionId,
                'error' => $e->getMessage()
            ]);

            // Save error message
            ChatMessage::create([
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => 'Sorry, I encountered an error processing your request. Please try again.',
            ]);

            throw new \Exception('Failed to process your request due to an internal error.');
        }
    }

    /**
     * Generate AI response using OpenAI API
     */
    private function generateAIResponse(string $query, string $gradeName): string
    {
        // Get OpenAI API key from environment
        $openaiApiKey = env('OPENAI_API_KEY');

        if (!$openaiApiKey) {
            Log::error('OpenAI API key not configured');
            return "I'm sorry, but the AI service is not properly configured right now. Please contact your instructor.";
        }

        $prompt = "You are Optimus, a friendly and knowledgeable AI assistant specialized in education and learning for {$gradeName} students.

You focus only on academic subjects such as math, science, history, literature, languages, and study skills.
If a student asks something unrelated to school, politely decline and remind them you only help with academic topics.

Student's question: {$query}

Guidelines

Provide clear, step-by-step explanations

Use examples and analogies when helpful

Be encouraging, patient, and supportive

Connect concepts to real-world applications when possible

Ask clarifying questions if needed

Keep responses focused on academic learning

Politely decline non-school questions (e.g., “I can only help with school-related subjects, let’s get back to learning!”)

Respond as a knowledgeable educational assistant.";

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$openaiApiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $prompt],
                    ['role' => 'user', 'content' => $query]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['choices'][0]['message']['content'] ?? 'I apologize, but I couldn\'t generate a response right now.';
            } else {
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return "I'm experiencing some technical difficulties. Please try again in a moment.";
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API call failed', ['error' => $e->getMessage()]);
            return "I'm sorry, but I'm having trouble connecting to the AI service right now. Please try again later.";
        }
    }

    /**
     * Get chat history for a session
     */
    public function getChatHistory(Request $request, $sessionId)
    {
        $user = Auth::user();

        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->with(['messages' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }])
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Chat session not found or access denied'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
                'messages' => $session->messages->map(function ($message) {
                    return [
                        'id' => $message->id,
                        'role' => $message->role,
                        'content' => $message->content,
                        'created_at' => $message->created_at,
                    ];
                }),
            ]
        ]);
    }

    /**
     * End a chat session
     */
    public function endSession(Request $request, $sessionId)
    {
        $user = Auth::user();

        $session = ChatSession::where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => 'Chat session not found or access denied'
            ], 404);
        }

        $session->endSession();

        return response()->json([
            'success' => true,
            'message' => 'Chat session ended successfully'
        ]);
    }
}
