<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KbEmbedding extends Model
{
    use HasFactory;

    protected $fillable = [
        'chunk_id',
        'model_name',
        'dim',
        'vector',
    ];

    protected $casts = [
        'vector' => 'array', // Store as JSON array
    ];

    // Relationships
    public function chunk()
    {
        return $this->belongsTo(KbChunk::class, 'chunk_id');
    }
}
