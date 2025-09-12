<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use Illuminate\Http\Request;

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
        $lessons = $chapter->lessons->map(function ($lesson) {
            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'type' => 'lesson',
                'isCompleted' => false,
                'duration' => null,
            ];
        });

        return response()->json([
            'id' => $chapter->id,
            'title' => $chapter->title,
            'subject_id' => $chapter->subject_id,
            'lessons' => $lessons,
        ]);
    }

    /**
     * Return list of chapters for a subject
     */
    public function forSubject(Request $request, $subjectId)
    {
        $chapters = Chapter::where('subject_id', $subjectId)
            ->orderBy('order')
            ->get()
            ->map(function ($chapter) {
                return [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'order' => $chapter->order,
                ];
            });

        return response()->json([
            'subject_id' => $subjectId,
            'chapters' => $chapters,
        ]);
    }
}
