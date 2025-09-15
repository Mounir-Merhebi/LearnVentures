<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Subject;
use App\Models\StudentQuiz;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;

class DashboardController extends Controller
{
    /**
     * Get dashboard data for authenticated user
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Total lessons
        $totalLessons = Lesson::count();

        // Completed lessons - use student_quizzes completed_at as proxy
        $completedLessons = StudentQuiz::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->distinct('quiz_id')
            ->count();

        $overallProgress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        // Subjects and per-subject progress
        $subjects = Subject::withCount(['chapters as lessons_count' => function ($q) {
            $q->join('lessons', 'chapters.id', '=', 'lessons.chapter_id')->selectRaw('count(lessons.id)');
        }])->get();

        $subjectData = $subjects->map(function ($subject) use ($user) {
            // total chapters in subject
            $totalChaptersInSubject = $subject->chapters()->count();

            // completed chapters in subject by user (via quizzes -> quiz.chapter_id)
            $completedChaptersInSubject = StudentQuiz::where('user_id', $user->id)
                ->whereNotNull('completed_at')
                ->join('quizzes', 'student_quizzes.quiz_id', '=', 'quizzes.id')
                ->where('quizzes.chapter_id', '!=', null)
                ->whereIn('quizzes.chapter_id', function ($q) use ($subject) {
                    $q->select('chapters.id')
                        ->from('chapters')
                        ->where('chapters.subject_id', $subject->id);
                })->distinct('student_quizzes.quiz_id')->count();

            $progressPercent = $totalChaptersInSubject > 0 ? round(($completedChaptersInSubject / $totalChaptersInSubject) * 100) : 0;

            return [
                'id' => $subject->id,
                'name' => $subject->title,
                'description' => $subject->description ?? null,
                'chapters' => "$totalChaptersInSubject chapters",
                'progress' => $progressPercent,
            ];
        });

        // Completed/total subjects
        $totalSubjects = Subject::count();
        // completedSubjects: subject where progress == 100
        $completedSubjects = $subjectData->filter(fn($s) => $s['progress'] === 100)->count();

        // averageScore: average of completed quiz scores for the user
        $averageScore = StudentQuiz::where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->avg('score');

        // Normalize to numeric (0 if no completed quizzes)
        $averageScore = $averageScore !== null ? round($averageScore, 1) : 0;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'overallProgress' => $overallProgress,
            'completedLessons' => $completedLessons,
            'totalLessons' => $totalLessons,
            'completedSubjects' => $completedSubjects,
            'totalSubjects' => $totalSubjects,
            'averageScore' => $averageScore,
            'subjects' => $subjectData->values(),
        ]);
    }
}


