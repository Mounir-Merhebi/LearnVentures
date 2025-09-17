<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'grade_id',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the user that owns the chat session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the grade that the chat session is scoped to.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the messages for this chat session.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }

    /**
     * Check if the session is active (not ended).
     */
    public function isActive(): bool
    {
        return is_null($this->ended_at);
    }

    /**
     * End the chat session.
     */
    public function endSession(): void
    {
        $this->update(['ended_at' => now()]);
    }

    /**
     * Get the duration of the session in minutes.
     */
    public function getDurationInMinutes(): ?float
    {
        if (!$this->started_at || !$this->ended_at) {
            return null;
        }

        return round($this->started_at->diffInMinutes($this->ended_at), 2);
    }
}
