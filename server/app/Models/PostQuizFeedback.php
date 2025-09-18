<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostQuizFeedback extends Model
{
	use HasFactory;

	protected $table = 'post_quiz_feedback';

	protected $fillable = [
		'student_quiz_id',
		'chapter_id',
		'overall_performance',
		'weak_areas',
		'recommendations',
		'study_plan',
		'recommended_lesson_ids',
		'analyzed_at',
	];

	protected $casts = [
		'weak_areas' => 'array',
		'recommendations' => 'array',
		'study_plan' => 'array',
		'recommended_lesson_ids' => 'array',
		'analyzed_at' => 'datetime',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];

	public function studentQuiz()
	{
		return $this->belongsTo(StudentQuiz::class, 'student_quiz_id');
	}
}


