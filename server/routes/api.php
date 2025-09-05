<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Common\AuthController;
use App\Http\Controllers\Common\AIAgentController;

Route::group(["prefix" =>"v0.1"], function(){
    Route::group(["middleware" => "auth:api"], function(){
        Route::group(["prefix" => "user"], function(){
            // AI Agent endpoints
            Route::post('/analyze-performance', [AIAgentController::class, 'analyzePerformance']);
            Route::post('/personalize-lesson', [AIAgentController::class, 'personalizeLesson']);
            Route::get('/analyses/{userId}', [AIAgentController::class, 'getUserAnalyses']);
            Route::get('/personalized-lessons/{userId}', [AIAgentController::class, 'getUserLessons']);
        });
        
        // AI Agent health check
        Route::get('/ai-health', [AIAgentController::class, 'healthCheck']);
    });
    Route::group(["prefix" => "guest"], function(){
        Route::post("/login", [AuthController::class, "login"]);
        Route::post("/register", [AuthController::class, "register"]);
    });
    
    // Simple test route
    Route::get('/test', function () {
        return response()->json(['message' => 'API is working!']);
    });
});