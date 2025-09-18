<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalizedLesson extends Model
{
	use HasFactory;

	protected $fillable = [
		'user_id',
		'lesson_id',
		'personalized_title',
		'personalized_content',
		'practical_examples',
		'generated_at',
	];

	protected $casts = [
		'practical_examples' => 'array',
		'generated_at' => 'datetime',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	public function lesson()
	{
		return $this->belongsTo(Lesson::class, 'lesson_id');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
}


