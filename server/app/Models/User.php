<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject {
    use HasFactory, Notifiable;

    protected $fillable = [
        'email','password','role','name',
        'hobbies','preferences','bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }

    public function getJWTIdentifier() {
        return $this->getKey();
    }

    public function getJWTCustomClaims() {
        return [];
    }

    // Relationships
    public function subjects()
    {
        return $this->hasMany(Subject::class, 'instructor_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'instructor_id');
    }

    public function personalizedLessons()
    {
        return $this->hasMany(PersonalizedLesson::class, 'user_id');
    }

    public function studentGradeEnrollments()
    {
        return $this->hasMany(StudentGradeEnrollment::class, 'user_id');
    }

    public function invitedEnrollments()
    {
        return $this->hasMany(StudentGradeEnrollment::class, 'invited_by');
    }

    public function chatSessions()
    {
        return $this->hasMany(ChatSession::class, 'user_id');
    }
}