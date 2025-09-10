<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbChunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'chunk_index',
        'text',
        'source_lesson_version',
        'content_hash',
    ];

    // Relationships
    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    public function embeddings()
    {
        return $this->hasMany(KbEmbedding::class, 'chunk_id');
    }
}
