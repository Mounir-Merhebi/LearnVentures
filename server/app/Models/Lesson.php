<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    use HasFactory;

    protected $fillable = [
        'chapter_id',
        'instructor_id',
        'title',
        'content',
        'order',
        'version',
    ];

    // Relationships
    public function chapter()
    {
        return $this->belongsTo(Chapter::class, 'chapter_id');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function personalizedLessons()
    {
        return $this->hasMany(PersonalizedLesson::class, 'lesson_id');
    }

    public function kbChunks()
    {
        return $this->hasMany(KbChunk::class, 'lesson_id');
    }

    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'lesson_id');
    }

    public function quizFeedback()
    {
        return $this->hasMany(PostQuizFeedback::class, 'lesson_id');
    }
}
