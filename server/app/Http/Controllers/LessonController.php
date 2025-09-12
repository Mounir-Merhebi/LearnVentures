<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\PersonalizedLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    /**
     * Return lesson details and any personalized lesson for the authenticated user
     */
    public function show(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        $userId = Auth::id();
        $personalized = null;

        if ($userId) {
            $personalized = PersonalizedLesson::where('user_id', $userId)
                ->where('lesson_id', $id)
                ->orderBy('generated_at', 'desc')
                ->first();
        }

        return response()->json([
            'id' => $lesson->id,
            'title' => $lesson->title,
            'content' => $lesson->content,
            'chapter_id' => $lesson->chapter_id,
            'personalized_lesson' => $personalized ? [
                'id' => $personalized->id,
                'personalized_title' => $personalized->personalized_title,
                'personalized_content' => $personalized->personalized_content,
                'practical_examples' => $personalized->practical_examples,
                'generated_at' => $personalized->generated_at,
            ] : null,
        ], 200);
    }
}
