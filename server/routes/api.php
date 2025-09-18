<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ModeratorExcelController;
use App\Http\Controllers\AdminChangeProposalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyChatReportController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\ProfileController;

Route::group(["prefix" =>"v0.1"], function(){
    // Guest routes (no auth required)
    Route::group(["prefix" => "guest"], function(){
        Route::post("/login", [AuthController::class, "login"]);
        Route::post("/register", [AuthController::class, "register"]);
    });

    // Authenticated routes
    Route::group(["middleware" => "auth:api"], function(){
        // Profile
        Route::get('/profile/me', [ProfileController::class, 'me']);
        Route::put('/profile', [ProfileController::class, 'update']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Chat endpoints
        Route::post('/chat/sessions', [ChatController::class, 'createSession']);
        Route::post('/chat/messages', [ChatController::class, 'sendMessage']);
        Route::get('/chat/sessions/{sessionId}/history', [ChatController::class, 'getChatHistory']);
        Route::post('/chat/sessions/{sessionId}/end', [ChatController::class, 'endSession']);

        // Daily chat reports
        Route::group(["prefix" => "reports"], function(){
            Route::post('/daily', [DailyChatReportController::class, 'upsert']);
            Route::get('/daily', [DailyChatReportController::class, 'index']);
            Route::get('/daily/{id}', [DailyChatReportController::class, 'show']);
            Route::post('/daily/{id}/mark-analyzed', [DailyChatReportController::class, 'markAnalyzed']);
        });

        // Chapter
        Route::get('/chapters/{id}', [\App\Http\Controllers\ChapterController::class, 'show']);
        // Lesson details (returns lesson and any personalized lesson for the authenticated user)
        Route::get('/lessons/{id}', [\App\Http\Controllers\LessonController::class, 'show']);
        Route::get('/subjects/{id}/chapters', [\App\Http\Controllers\ChapterController::class, 'forSubject']);
        Route::get('/subjects/{id}', [\App\Http\Controllers\SubjectController::class, 'show']);

        // Quiz endpoints
        Route::group(["prefix" => "quiz"], function(){
            Route::get('/chapter/{chapterId}', [QuizController::class, 'getByChapter']);
            Route::post('/{quizId}/start', [QuizController::class, 'startQuiz']);
            Route::post('/{quizId}/submit', [QuizController::class, 'submitQuiz']);
            Route::get('/attempt/{attemptId}', [QuizController::class, 'getResults']);
            Route::get('/attempts', [QuizController::class, 'getUserAttempts']);
            Route::post('/analyze-performance', [QuizController::class, 'analyzePerformance']);
            Route::get('/feedback/{studentQuizId}', [QuizController::class, 'getFeedback']);
        });


        // Excel Moderation routes
        // Moderator routes
        Route::group(["prefix" => "mod", "middleware" => function ($request, $next) {
            if (!in_array(auth()->user()->role, ['Moderator', 'Admin'])) {
                return response()->json(['message' => 'Access denied'], 403);
            }
            return $next($request);
        }], function(){
            Route::get('/excel/baseline', [ModeratorExcelController::class, 'baseline']);
            Route::post('/proposals', [ModeratorExcelController::class, 'storeProposal']);
        });

        // Admin routes
        Route::group(["prefix" => "admin", "middleware" => function ($request, $next) {
            $user = auth()->user();
            if (!$user || $user->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }
            return $next($request);
        }], function(){
            // Content management routes
            Route::get('/grades', [\App\Http\Controllers\AdminContentController::class, 'getGrades']);
            Route::get('/instructors', [\App\Http\Controllers\AdminContentController::class, 'getInstructors']);
            Route::group(["prefix" => "content"], function(){
                // Subjects
                Route::get('/subjects', [\App\Http\Controllers\AdminContentController::class, 'getSubjects']);
                Route::post('/subjects', [\App\Http\Controllers\AdminContentController::class, 'createSubject']);
                Route::put('/subjects/{subjectId}', [\App\Http\Controllers\AdminContentController::class, 'updateSubject']);
                Route::delete('/subjects/{subjectId}', [\App\Http\Controllers\AdminContentController::class, 'deleteSubject']);

                // Chapters
                Route::post('/chapters', [\App\Http\Controllers\AdminContentController::class, 'createChapter']);
                Route::put('/chapters/{chapterId}', [\App\Http\Controllers\AdminContentController::class, 'updateChapter']);
                Route::delete('/chapters/{chapterId}', [\App\Http\Controllers\AdminContentController::class, 'deleteChapter']);

                // Lessons
                Route::post('/lessons', [\App\Http\Controllers\AdminContentController::class, 'createLesson']);
                Route::put('/lessons/{lessonId}', [\App\Http\Controllers\AdminContentController::class, 'updateLesson']);
                Route::delete('/lessons/{lessonId}', [\App\Http\Controllers\AdminContentController::class, 'deleteLesson']);

                // Quizzes
                Route::post('/quizzes', [\App\Http\Controllers\AdminContentController::class, 'createQuiz']);
                Route::put('/quizzes/{quizId}', [\App\Http\Controllers\AdminContentController::class, 'updateQuiz']);
                Route::delete('/quizzes/{quizId}', [\App\Http\Controllers\AdminContentController::class, 'deleteQuiz']);
            });

            // Existing admin routes
            Route::get('/proposals', [AdminChangeProposalController::class, 'index']);
            Route::get('/proposals/{id}', [AdminChangeProposalController::class, 'show']);
            Route::post('/proposals/{id}/decision', [AdminChangeProposalController::class, 'decision']);
        });
    });
});
