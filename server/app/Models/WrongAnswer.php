<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WrongAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_topic',
        'question',
        'user_answer',
        'correct_answer',
        'analyzed',
        'performance_analysis_id'
    ];

    protected $casts = [
        'analyzed' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function performanceAnalysis()
    {
        return $this->belongsTo(PerformanceAnalysis::class);
    }
}
