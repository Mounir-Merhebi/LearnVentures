<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentQuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_quiz_id',
        'question_id',
        'selected_answer',
        'is_correct',
        'correct_option_snapshot',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    // Relationships
    public function studentQuiz()
    {
        return $this->belongsTo(StudentQuiz::class, 'student_quiz_id');
    }

    public function question()
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }
}
