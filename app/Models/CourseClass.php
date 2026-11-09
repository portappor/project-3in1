<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_mk',
        'nama_mk',
        'sks',
        'nama_kelas',
        'prodi',
        'semester',
        'tahun_ajaran',
        'dosen_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'ruang',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sks' => 'integer',
            'semester' => 'integer',
        ];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_class_students', 'course_class_id', 'student_id')
            ->withTimestamps();
    }

    public function virtualClassrooms(): HasMany
    {
        return $this->hasMany(VirtualClassroom::class, 'course_class_id');
    }

    public function activeSession(): ?VirtualClassroom
    {
        return $this->virtualClassrooms()
            ->where('status', 'live')
            ->latest()
            ->first();
    }
}
