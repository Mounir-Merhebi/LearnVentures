<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    /**
     * Return lesson details
     */
    public function show(Request $request, $id)
    {
        $lesson = Lesson::findOrFail($id);

        return response()->json([
            'id' => $lesson->id,
            'title' => $lesson->title,
            'content' => $lesson->content,
            'chapter_id' => $lesson->chapter_id,
        ], 200);
    }
}
