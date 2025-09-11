<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\AuthController;
use App\Http\Controllers\Common\AIAgentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ModeratorExcelController;
use App\Http\Controllers\AdminChangeProposalController;
use App\Http\Controllers\DashboardController;

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
            Route::get('/proposals', [AdminChangeProposalController::class, 'index']);
            Route::get('/proposals/{id}', [AdminChangeProposalController::class, 'show']);
            Route::post('/proposals/{id}/decision', [AdminChangeProposalController::class, 'decision']);
        });
    });
});