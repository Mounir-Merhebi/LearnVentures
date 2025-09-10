<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    // Relationships
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'grade_id');
    }

    public function studentGradeEnrollments()
    {
        return $this->hasMany(StudentGradeEnrollment::class, 'grade_id');
    }

    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class, 'grade_id');
    }
}
