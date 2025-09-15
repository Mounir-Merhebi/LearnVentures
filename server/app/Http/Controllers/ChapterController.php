<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\PersonalizedLesson;
use App\Models\StudentQuiz;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChapterController extends Controller
{
    /**
     * Return chapter details including topics (lessons grouped by topic)
     */
    public function show(Request $request, $id)
    {
        $chapter = Chapter::with(['lessons'])->findOrFail($id);

        // Group lessons by topic placeholder: chapters currently don't have topic model,
        // so we'll return lessons as a single topic for now.
        $userId = Auth::id();

        // Preload any personalized lessons for this user that belong to this chapter
        $personalizedMap = collect([]);
        if ($userId) {
            $lessonIds = $chapter->lessons->pluck('id')->toArray();
            $personalized = PersonalizedLesson::where('user_id', $userId)
                ->whereIn('lesson_id', $lessonIds)
                ->orderBy('generated_at', 'desc')
                ->get()
                ->keyBy('lesson_id');

            $personalizedMap = $personalized;
        }

        $lessons = $chapter->lessons->map(function ($lesson) use ($userId, $personalizedMap, $chapter) {
            // determine type roughly from content
            $type = 'lesson';
            if (!empty($lesson->content) && (stripos($lesson->content, '<iframe') !== false || stripos($lesson->content, '<video') !== false)) {
                $type = 'video';
            }

            // check if user completed the chapter quiz (quizzes are now per chapter, not per lesson)
            $completed = false;
            if ($userId) {
                // Check if there's a completed quiz for this chapter
                $quiz = Quiz::where('chapter_id', $chapter->id)->first();
                if ($quiz) {
                    $completed = StudentQuiz::where('user_id', $userId)
                        ->where('quiz_id', $quiz->id)
                        ->whereNotNull('completed_at')
                        ->exists();
                }
            }

            $personalizedEntry = null;
            if ($personalizedMap->has($lesson->id)) {
                $p = $personalizedMap->get($lesson->id);
                $personalizedEntry = [
                    'id' => $p->id,
                    'personalized_title' => $p->personalized_title,
                    'personalized_content' => $p->personalized_content,
                    'practical_examples' => $p->practical_examples,
                    'generated_at' => $p->generated_at,
                ];
            }

            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'type' => $type,
                'isCompleted' => $completed,
                'duration' => null,
                'personalized' => $personalizedEntry,
            ];
        });

        return response()->json([
            'id' => $chapter->id,
            'title' => $chapter->title,
            'description' => $chapter->description ?? null,
            'cover_photo' => $chapter->cover_photo ?? null,
            'subject_id' => $chapter->subject_id,
            'lessons' => $lessons,
        ]);
    }

    /**
     * Return list of chapters for a subject
     */
    public function forSubject(Request $request, $subjectId)
    {
        // include lessons_count so frontend can show lesson numbers without extra requests
        $chapters = Chapter::where('subject_id', $subjectId)
            ->withCount('lessons')
            ->orderBy('order')
            ->get()
            ->map(function ($chapter) {
                return [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'order' => $chapter->order,
                    'lessons_count' => $chapter->lessons_count,
                    'description' => $chapter->description ?? null,
                    'cover_photo' => $chapter->cover_photo ?? null,
                ];
            });

        return response()->json([
            'subject_id' => $subjectId,
            'chapters' => $chapters,
        ]);
    }
}
