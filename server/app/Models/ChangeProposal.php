<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'moderator_id',
        'scope',
        'excel_hash',
        'excel_path',
        'excel_snapshot',
        'db_snapshot',
        'diff_json',
        'status',
        'decided_by',
    ];

    protected function casts(): array
    {
        return [
            'scope' => 'array',
            'excel_snapshot' => 'array',
            'db_snapshot' => 'array',
            'diff_json' => 'array',
        ];
    }

    // Relationships
    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function decisionMaker()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
