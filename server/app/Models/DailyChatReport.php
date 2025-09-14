<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $student_id
 * @property \Illuminate\Support\Carbon\CarbonInterface $report_date
 * @property string|null $tldr
 * @property array|null $key_topics
 * @property array|null $misconceptions
 * @property array|null $next_actions
 * @property array|null $stats
 * @property array|null $full_summary
 */
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
    ];

    protected $casts = [
        'key_topics' => 'array',
        'misconceptions' => 'array',
        'next_actions' => 'array',
        'stats' => 'array',
        'full_summary' => 'array',
        'report_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}

