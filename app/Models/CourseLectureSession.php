<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseLectureSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_class_id',
        'dosen_id',
        'pertemuan_ke',
        'tanggal',
        'mode',
        'topik',
        'ruang',
        'virtual_classroom_id',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pertemuan_ke' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function courseClass(): BelongsTo
    {
        return $this->belongsTo(CourseClass::class, 'course_class_id');
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function virtualClassroom(): BelongsTo
    {
        return $this->belongsTo(VirtualClassroom::class, 'virtual_classroom_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CourseLectureAttendance::class, 'lecture_session_id');
    }

    public function isOnline(): bool
    {
        return $this->mode === 'online';
    }

    public function isOffline(): bool
    {
        return $this->mode === 'offline';
    }
}
