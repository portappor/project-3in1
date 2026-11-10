<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseLectureAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'lecture_session_id',
        'student_id',
        'status',
        'catatan',
        'waktu_presensi',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'waktu_presensi' => 'datetime',
        ];
    }

    public function lectureSession(): BelongsTo
    {
        return $this->belongsTo(CourseLectureSession::class, 'lecture_session_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
