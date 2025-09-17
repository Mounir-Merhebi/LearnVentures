<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentQuiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'quiz_id',
        'score',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'score' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class, 'quiz_id');
    }

    public function answers()
    {
        return $this->hasMany(StudentQuizAnswer::class, 'student_quiz_id');
    }

    // Helper methods
    public function isCompleted()
    {
        return !is_null($this->completed_at);
    }

    public function getDurationInMinutes()
    {
        return $this->duration_seconds ? round($this->duration_seconds / 60, 1) : null;
    }
}
