<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class VirtualClassroom extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_code',
        'title',
        'dosen_id',
        'status',
        'presentation_path',
        'presentation_name',
        'current_slide',
        'total_slides',
        'is_all_mic_muted',
    ];

    protected function casts(): array
    {
        return [
            'current_slide' => 'integer',
            'total_slides' => 'integer',
            'is_all_mic_muted' => 'boolean',
        ];
    }

    public function dosen(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dosen_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ClassroomAttendance::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(ClassroomChat::class)->oldest();
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_attendances')
            ->withPivot(['role', 'joined_at', 'last_seen_at', 'left_at', 'is_hand_raised', 'is_mic_muted', 'status'])
            ->withTimestamps();
    }

    public function getPresentationUrlAttribute(): ?string
    {
        if (! $this->presentation_path) {
            return null;
        }

        return Storage::disk('public')->url($this->presentation_path);
    }
}

