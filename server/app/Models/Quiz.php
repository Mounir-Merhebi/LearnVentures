<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'title',
        'question_count',
        'time_limit_seconds',
    ];

    // Relationships
    public function chapter()
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class, 'quiz_id');
    }

    public function studentQuizzes()
    {
        return $this->hasMany(StudentQuiz::class, 'quiz_id');
    }
}
