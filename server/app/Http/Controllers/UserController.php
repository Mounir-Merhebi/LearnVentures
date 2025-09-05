<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::query()->orderByDesc('created_at')->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','min:8'],
            'role' => ['required', Rule::in(['Student','Instructor','Moderator','Admin'])],
            'name' => ['required','string','max:255'],
            'hobbies' => ['nullable','string'],
            'preferences' => ['nullable','string'],
            'bio' => ['nullable','string'],
            'excel_sheet_path' => ['nullable','string','max:255'],
        ]);

        $user = User::create([
            'id' => (string) Str::uuid(),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'name' => $data['name'],
            'hobbies' => $data['hobbies'] ?? null,
            'preferences' => $data['preferences'] ?? null,
            'bio' => $data['bio'] ?? null,
            'excel_sheet_path' => $data['excel_sheet_path'] ?? null,
        ]);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'email' => ['sometimes','email','max:255', Rule::unique('users','email')->ignore($user->id, 'id')],
            'password' => ['sometimes','min:8'],
            'role' => ['sometimes', Rule::in(['Student','Instructor','Moderator','Admin'])],
            'name' => ['sometimes','string','max:255'],
            'hobbies' => ['nullable','string'],
            'preferences' => ['nullable','string'],
            'bio' => ['nullable','string'],
            'excel_sheet_path' => ['nullable','string','max:255'],
        ]);

        if (array_key_exists('password', $data)) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->fill($data)->save();

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['deleted' => true]);
    }
}
