<?php

namespace App\Services\Common;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class AuthService {
    public static function login(Request $request){
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();
        $user->token = $token;
        return $user;
    }

    public static function register(Request $request){
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = $request->role ?? 'Student';
        $user->hobbies = $request->hobbies;
        $user->preferences = $request->preferences;
        $user->bio = $request->bio;
        $user->created_at = now();
        $user->save();

        $token = JWTAuth::fromUser($user);

        $user->token = $token;
        return $user;
    }
}