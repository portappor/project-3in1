<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassroomAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'virtual_classroom_id',
        'user_id',
        'role',
        'joined_at',
        'last_seen_at',
        'left_at',
        'is_hand_raised',
        'is_mic_muted',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'left_at' => 'datetime',
            'is_hand_raised' => 'boolean',
            'is_mic_muted' => 'boolean',
        ];
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(VirtualClassroom::class, 'virtual_classroom_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
