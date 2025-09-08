<?php

namespace App\Http\Controllers\Common;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use  App\Services\Common\AuthService;

class AuthController extends Controller {


    public function login(Request $request){
        try {
            $user = AuthService::login($request);
            
            if($user) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'payload' => $user
                ], 200);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
                'payload' => null
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage(),
                'payload' => null
            ], 500);
        }
    }

    public function register(Request $request){
        try {
            $user = AuthService::register($request);
            
            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'payload' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed: ' . $e->getMessage(),
                'payload' => null
            ], 400);
        }
    }
}