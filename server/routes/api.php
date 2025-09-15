<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\AuthController;
use App\Http\Controllers\Common\AIAgentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ModeratorExcelController;
use App\Http\Controllers\AdminChangeProposalController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyChatReportController;
use App\Http\Controllers\QuizController;

Route::group(["prefix" =>"v0.1"], function(){
    // Guest routes (no auth required)
    Route::group(["prefix" => "guest"], function(){
        Route::post("/login", [AuthController::class, "login"]);
        Route::post("/register", [AuthController::class, "register"]);
    });

    // Authenticated routes
    Route::group(["middleware" => "auth:api"], function(){
        Route::group(["prefix" => "user"], function(){
            // AI Agent endpoints
            Route::post('/analyze-performance', [AIAgentController::class, 'analyzePerformance']);
            Route::post('/personalize-lesson', [AIAgentController::class, 'personalizeLesson']);
            Route::get('/analyses/{userId}', [AIAgentController::class, 'getUserAnalyses']);
            Route::get('/personalized-lessons/{userId}', [AIAgentController::class, 'getUserLessons']);
            Route::get('/wrong-answers/{userId}', [AIAgentController::class, 'getWrongAnswers']);
        });
        
        // AI Agent health check
        Route::get('/ai-health', [AIAgentController::class, 'healthCheck']);
        Route::get('/test-health', [AIAgentController::class, 'testHealth']);

        // Chat endpoints
        Route::post('/chat/sessions', [ChatController::class, 'createSession']);
        Route::post('/chat/messages', [ChatController::class, 'sendMessage']);

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Daily chat reports
        Route::group(["prefix" => "reports"], function(){
            Route::post('/daily', [DailyChatReportController::class, 'upsert']);
            Route::get('/daily', [DailyChatReportController::class, 'index']);
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
        });

        // Quiz performance analysis endpoints
        Route::post('/quiz/analyze-performance', [AIAgentController::class, 'analyzeQuizPerformance']);
        Route::get('/quiz/feedback/{studentQuizId}', [AIAgentController::class, 'getQuizFeedback']);

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
            if (auth()->user()->role !== 'Admin') {
                return response()->json(['message' => 'Access denied'], 403);
            }
            return $next($request);
        }], function(){
            // Content management routes
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
