<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalizedLesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_lesson_title',
        'original_lesson_content',
        'personalized_title',
        'personalized_content',
        'learning_approach',
        'practical_examples',
        'next_steps',
        'generated_at'
    ];

    protected $casts = [
        'practical_examples' => 'array',
        'next_steps' => 'array',
        'generated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
