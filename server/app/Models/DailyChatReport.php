<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyChatReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'report_date',
        'tldr',
        'key_topics',
        'misconceptions',
        'next_actions',
        'stats',
        'full_summary',
        'analyzed_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'key_topics' => 'array',
        'misconceptions' => 'array',
        'next_actions' => 'array',
        'stats' => 'array',
        'analyzed_at' => 'datetime',
    ];

    /**
     * Get the student that this report belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Scope to get reports for a specific student.
     */
    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Scope to get reports for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('report_date', $date);
    }

    /**
     * Scope to get reports within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('report_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get analyzed reports.
     */
    public function scopeAnalyzed($query)
    {
        return $query->whereNotNull('analyzed_at');
    }

    /**
     * Scope to get pending analysis reports.
     */
    public function scopePendingAnalysis($query)
    {
        return $query->whereNull('analyzed_at');
    }

    /**
     * Check if the report has been analyzed.
     */
    public function isAnalyzed(): bool
    {
        return !is_null($this->analyzed_at);
    }

    /**
     * Mark the report as analyzed.
     */
    public function markAsAnalyzed(): void
    {
        $this->update(['analyzed_at' => now()]);
    }
}
