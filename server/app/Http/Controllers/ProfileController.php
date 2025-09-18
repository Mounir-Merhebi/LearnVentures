<?php

namespace App\Http\Controllers;

use App\Models\PersonalizedLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
	public function me()
	{
		$user = Auth::user();
		return response()->json([
			'success' => true,
			'data' => [
				'id' => $user->id,
				'name' => $user->name,
				'email' => $user->email,
				'hobbies' => $user->hobbies,
				'preferences' => $user->preferences,
				'bio' => $user->bio,
			]
		]);
	}

	public function update(Request $request)
	{
		$user = Auth::user();
		$validated = $request->validate([
			'name' => 'sometimes|string|max:255',
			'hobbies' => 'nullable|string|max:2000',
			'preferences' => 'nullable|string|max:2000',
			'bio' => 'nullable|string|max:4000',
		]);

		$original = [
			'hobbies' => $user->hobbies,
			'preferences' => $user->preferences,
			'bio' => $user->bio,
		];

		$user->fill($validated);
		$user->save();

		// If personalization-relevant fields changed, invalidate cached personalized lessons
		if (
			array_key_exists('hobbies', $validated) && $validated['hobbies'] !== $original['hobbies']
			|| array_key_exists('preferences', $validated) && $validated['preferences'] !== $original['preferences']
			|| array_key_exists('bio', $validated) && $validated['bio'] !== $original['bio']
		) {
			PersonalizedLesson::where('user_id', $user->id)->delete();
		}

		return response()->json([
			'success' => true,
			'message' => 'Profile updated successfully'
		]);
	}
}


