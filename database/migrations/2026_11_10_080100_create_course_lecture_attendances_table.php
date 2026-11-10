<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lecture_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lecture_session_id')->constrained('course_lecture_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->string('catatan', 255)->nullable();
            $table->timestamp('waktu_presensi')->nullable();
            $table->timestamps();

            $table->unique(['lecture_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lecture_attendances');
    }
};
