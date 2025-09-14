<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Return subject metadata by id
     */
    public function show(Request $request, $id)
    {
        $subject = Subject::findOrFail($id);

        return response()->json([
            'id' => $subject->id,
            'title' => $subject->title,
            'description' => $subject->description ?? null,
        ]);
    }
}
