<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lesson_topic',
        'overall_performance',
        'weak_areas',
        'recommendations',
        'study_plan',
        'analyzed_at'
    ];

    protected $casts = [
        'weak_areas' => 'array',
        'recommendations' => 'array',
        'analyzed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wrongAnswers()
    {
        return $this->hasMany(WrongAnswer::class);
    }
}
