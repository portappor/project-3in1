<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'prodi',
        'fakultas',
        'status_aktif',
        'password',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'status_aktif' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDosen(): bool
    {
        return $this->role === 'dosen';
    }

    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    public function hostedClassrooms(): HasMany
    {
        return $this->hasMany(VirtualClassroom::class, 'dosen_id');
    }

    public function teachingClasses(): HasMany
    {
        return $this->hasMany(CourseClass::class, 'dosen_id');
    }

    public function enrolledClasses(): BelongsToMany
    {
        return $this->belongsToMany(CourseClass::class, 'course_class_students', 'student_id', 'course_class_id')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassroomAttendance::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(ClassroomChat::class);
    }
}
