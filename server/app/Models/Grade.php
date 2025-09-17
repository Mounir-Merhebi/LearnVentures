<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\GradeFactory;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'level',
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

    protected static function newFactory()
    {
        return GradeFactory::new();
    }
}
