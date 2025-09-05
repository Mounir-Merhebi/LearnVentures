<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model
{
    use HasFactory;

    // table is 'users' by default

    // string/uuid PK
    protected $keyType = 'string';
    public $incrementing = false;

    // timestamps: only created_at
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'id',
        'email',
        'password',
        'role',
        'name',
        'hobbies',
        'preferences',
        'bio',
        'excel_sheet_path',
        'created_at',
    ];

    protected $hidden = ['password'];
}
