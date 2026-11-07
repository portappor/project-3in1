<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Ruang Kelas Virtual
        Schema::create('virtual_classrooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_code', 20)->unique();
            $table->string('title');
            $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['scheduled', 'live', 'ended'])->default('live');
            $table->string('presentation_path')->nullable();
            $table->string('presentation_name')->nullable();
            $table->integer('current_slide')->default(1);
            $table->integer('total_slides')->default(1);
            $table->boolean('is_all_mic_muted')->default(false);
            $table->timestamps();
        });

        // 2. Tabel Presensi Kehadiran
        Schema::create('classroom_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('participant');
            $table->timestamp('joined_at');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_hand_raised')->default(false);
            $table->boolean('is_mic_muted')->default(false);
            $table->string('status', 20)->default('hadir');
            $table->timestamps();

            $table->unique(['virtual_classroom_id', 'user_id']);
        });

        // 3. Tabel Log Chat Diskusi Ruang Kelas
        Schema::create('classroom_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_chats');
        Schema::dropIfExists('classroom_attendances');
        Schema::dropIfExists('virtual_classrooms');
    }
};

