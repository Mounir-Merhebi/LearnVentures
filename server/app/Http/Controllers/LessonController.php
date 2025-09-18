<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\PersonalizedLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LessonController extends Controller
{
    /**
     * Return lesson details
     */
    public function show(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);
        $user = Auth::user();

        $personalized = null;
        if ($user) {
            $personalized = PersonalizedLesson::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();

            if (!$personalized) {
                // Attempt generation via OpenAI
                $apiKey = env('OPENAI_API_KEY');
                $model = env('LESSON_PERSONALIZER_MODEL', env('QUIZ_FEEDBACK_MODEL', 'gpt-3.5-turbo'));
                if ($apiKey) {
                    try {
                        $system = [
                            'role' => 'system',
                            'content' => 'You personalize educational lessons. Return STRICT JSON with keys: '
                                . '{"personalized_title": string, "personalized_content": string, "practical_examples": [string]}. '
                                . 'Rewrite to match the student profile while preserving core ideas. Keep it concise and clear.'
                        ];

                        $userMsg = [
                            'role' => 'user',
                            'content' => json_encode([
                                'student_profile' => [
                                    'name' => $user->name,
                                    'hobbies' => $user->hobbies,
                                    'preferences' => $user->preferences,
                                    'bio' => $user->bio,
                                ],
                                'lesson' => [
                                    'title' => $lesson->title,
                                    'content' => $lesson->content,
                                ]
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                        ];

                        $payload = [
                            'model' => $model,
                            'messages' => [$system, $userMsg],
                            'temperature' => 0.4,
                            'max_tokens' => 900,
                        ];
                        if (preg_match('/gpt-4o|gpt-4\.1|o3|4o|mini/i', $model)) {
                            $payload['response_format'] = ['type' => 'json_object'];
                        }

                        $res = Http::withHeaders([
                            'Authorization' => "Bearer {$apiKey}",
                            'Content-Type' => 'application/json',
                        ])->post('https://api.openai.com/v1/chat/completions', $payload);

                        if ($res->successful()) {
                            $data = $res->json();
                            $text = $data['choices'][0]['message']['content'] ?? '';
                            $jsonText = $text;
                            $first = strpos($jsonText, '{');
                            $last = strrpos($jsonText, '}');
                            if ($first !== false && $last !== false && $last >= $first) {
                                $jsonText = substr($jsonText, $first, $last - $first + 1);
                            }
                            $decoded = json_decode($jsonText, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                $personalized = PersonalizedLesson::create([
                                    'user_id' => $user->id,
                                    'lesson_id' => $lesson->id,
                                    'personalized_title' => $decoded['personalized_title'] ?? $lesson->title,
                                    'personalized_content' => $decoded['personalized_content'] ?? $lesson->content,
                                    'practical_examples' => $decoded['practical_examples'] ?? [],
                                    'generated_at' => now(),
                                ]);
                            } else {
                                Log::warning('Lesson personalization parse failed', [
                                    'json_error' => json_last_error_msg(),
                                    'preview' => substr($text, 0, 300)
                                ]);
                            }
                        } else {
                            Log::error('OpenAI error personalizing lesson', [
                                'status' => $res->status(),
                                'body' => $res->body()
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('OpenAI call failed (lesson personalization)', ['error' => $e->getMessage()]);
                    }
                }
            }
        }

        return response()->json([
            'id' => $lesson->id,
            'title' => $personalized->personalized_title ?? $lesson->title,
            'content' => $personalized->personalized_content ?? $lesson->content,
            'chapter_id' => $lesson->chapter_id,
            'personalized' => (bool)$personalized,
            'examples' => $personalized->practical_examples ?? [],
        ], 200);
    }
}
