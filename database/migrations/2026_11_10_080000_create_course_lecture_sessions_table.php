<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_lecture_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained('course_classes')->cascadeOnDelete();
            $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('pertemuan_ke')->default(1);
            $table->date('tanggal');
            $table->enum('mode', ['online', 'offline'])->default('offline');
            $table->string('topik', 255)->nullable();
            $table->string('ruang', 100)->nullable();
            $table->foreignId('virtual_classroom_id')->nullable()->constrained('virtual_classrooms')->nullOnDelete();
            $table->enum('status', ['aktif', 'selesai'])->default('aktif');
            $table->timestamps();

            $table->index(['course_class_id', 'pertemuan_ke']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lecture_sessions');
    }
};
