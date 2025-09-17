<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'role',
        'content',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the chat session that owns the message.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }

    /**
     * Get the user that sent this message (if it's a user message).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if this is a user message.
     */
    public function isUserMessage(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if this is an assistant message.
     */
    public function isAssistantMessage(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Get the message content with a length limit for previews.
     */
    public function getPreview(int $length = 100): string
    {
        return strlen($this->content) > $length
            ? substr($this->content, 0, $length) . '...'
            : $this->content;
    }

    /**
     * Scope to get messages for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->whereHas('session', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope to get messages for a specific grade.
     */
    public function scopeForGrade($query, $gradeId)
    {
        return $query->whereHas('session', function ($q) use ($gradeId) {
            $q->where('grade_id', $gradeId);
        });
    }
}
