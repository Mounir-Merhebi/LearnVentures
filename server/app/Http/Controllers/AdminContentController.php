<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminContentController extends Controller
{
    /**
     * Get all grades
     */
    public function getGrades(): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $grades = Grade::all();

            return response()->json([
                'success' => true,
                'data' => $grades
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch grades',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all instructors
     */
    public function getInstructors(): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $instructors = User::where('role', 'Instructor')->get();

            return response()->json([
                'success' => true,
                'data' => $instructors
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch instructors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all subjects with their chapters and lessons
     */
    public function getSubjects(): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $subjects = Subject::with(['chapters.lessons', 'chapters.quiz'])->get();

            return response()->json([
                'success' => true,
                'data' => $subjects
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subjects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new subject
     */
    public function createSubject(Request $request): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'grade_id' => 'required|exists:grades,id',
                'instructor_id' => 'required|exists:users,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $subject = Subject::create([
                'grade_id' => $request->grade_id,
                'instructor_id' => $request->instructor_id,
                'title' => $request->title,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subject created successfully',
                'data' => $subject
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create subject',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a subject
     */
    public function updateSubject(Request $request, $subjectId): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $subject = Subject::find($subjectId);
            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found'
                ], 404);
            }

            $subject->update([
                'title' => $request->title,
                'description' => $request->description
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subject updated successfully',
                'data' => $subject
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update subject',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a subject
     */
    public function deleteSubject($subjectId): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $subject = Subject::find($subjectId);
            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found'
                ], 404);
            }

            $subject->delete();

            return response()->json([
                'success' => true,
                'message' => 'Subject deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete subject',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new chapter for a subject
     */
    public function createChapter(Request $request): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'subject_id' => 'required|exists:subjects,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cover_photo' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $chapter = Chapter::create([
                'subject_id' => $request->subject_id,
                'title' => $request->title,
                'description' => $request->description,
                'cover_photo' => $request->cover_photo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chapter created successfully',
                'data' => $chapter->load('subject')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create chapter',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a chapter
     */
    public function updateChapter(Request $request, $chapterId): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'cover_photo' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $chapter = Chapter::find($chapterId);
            if (!$chapter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chapter not found'
                ], 404);
            }

            $chapter->update([
                'title' => $request->title,
                'description' => $request->description,
                'cover_photo' => $request->cover_photo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Chapter updated successfully',
                'data' => $chapter->load('subject')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update chapter',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a chapter
     */
    public function deleteChapter($chapterId): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $chapter = Chapter::find($chapterId);
            if (!$chapter) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chapter not found'
                ], 404);
            }

            $chapter->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chapter deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chapter',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new lesson for a chapter
     */
    public function createLesson(Request $request): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'chapter_id' => 'required|exists:chapters,id',
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'concept_slug' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Get the chapter to find the subject's instructor
            $chapter = Chapter::with('subject')->find($request->chapter_id);
            if (!$chapter || !$chapter->subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Chapter or subject not found'
                ], 404);
            }

            $lesson = Lesson::create([
                'chapter_id' => $request->chapter_id,
                'instructor_id' => $chapter->subject->instructor_id,
                'title' => $request->title,
                'content' => $request->content,
                'concept_slugs' => $request->concept_slug ? json_encode([$request->concept_slug]) : null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson created successfully',
                'data' => $lesson->load('chapter.subject')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a lesson
     */
    public function updateLesson(Request $request, $lessonId): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'concept_slug' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            $lesson->update([
                'title' => $request->title,
                'content' => $request->content,
                'concept_slug' => $request->concept_slug
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lesson updated successfully',
                'data' => $lesson->load('chapter.subject')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a lesson
     */
    public function deleteLesson($lessonId): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $lesson = Lesson::find($lessonId);
            if (!$lesson) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lesson not found'
                ], 404);
            }

            $lesson->delete();

            return response()->json([
                'success' => true,
                'message' => 'Lesson deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete lesson',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a quiz for a chapter
     */
    public function createQuiz(Request $request): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'chapter_id' => 'required|exists:chapters,id',
                'title' => 'required|string|max:255',
                'time_limit_seconds' => 'nullable|integer|min:1',
                'questions' => 'required|array|min:1',
                'questions.*.body' => 'required|string',
                'questions.*.options_json' => 'required|string',
                'questions.*.correct_option' => 'required|string',
                'questions.*.order' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Create quiz directly attached to chapter
                $quiz = Quiz::create([
                    'chapter_id' => $request->chapter_id,
                    'title' => $request->title,
                    'time_limit_seconds' => $request->time_limit_seconds,
                    'question_count' => count($request->questions)
                ]);

                // Create questions
                foreach ($request->questions as $index => $questionData) {
                    QuizQuestion::create([
                        'quiz_id' => $quiz->id,
                        'body' => $questionData['body'],
                        'options_json' => $questionData['options_json'],
                        'correct_option' => $questionData['correct_option'],
                        // Enforce unique sequential order per quiz to avoid unique index violations
                        'order' => $index + 1,
                        'concept_slug' => $questionData['concept_slug'] ?? null,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Quiz created successfully',
                    'data' => $quiz->load('questions', 'chapter.subject')
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a quiz
     */
    public function updateQuiz(Request $request, $quizId): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'time_limit_seconds' => 'nullable|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 400);
            }

            $quiz = Quiz::find($quizId);
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], 404);
            }

            $quiz->update([
                'title' => $request->title,
                'time_limit_seconds' => $request->time_limit_seconds
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Quiz updated successfully',
                'data' => $quiz->load('questions', 'chapter.subject')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a quiz
     */
    public function deleteQuiz($quizId): JsonResponse
    {
        try {
            if (Auth::user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }

            $quiz = Quiz::find($quizId);
            if (!$quiz) {
                return response()->json([
                    'success' => false,
                    'message' => 'Quiz not found'
                ], 404);
            }

            $quiz->delete();

            return response()->json([
                'success' => true,
                'message' => 'Quiz deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete quiz',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
