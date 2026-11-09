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
        Schema::create('course_classes', function (Blueprint $table) {
            $table->id();
            $table->string('kode_mk', 50);
            $table->string('nama_mk', 255);
            $table->unsignedInteger('sks')->default(3);
            $table->string('nama_kelas', 100);
            $table->string('prodi', 100);
            $table->unsignedInteger('semester')->default(1);
            $table->string('tahun_ajaran', 50)->default('2026/2027 Ganjil');
            $table->foreignId('dosen_id')->constrained('users')->cascadeOnDelete();
            $table->string('hari', 20);
            $table->string('jam_mulai', 10);
            $table->string('jam_selesai', 10);
            $table->string('ruang', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('course_class_students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_class_id')->constrained('course_classes')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['course_class_id', 'student_id']);
        });

        Schema::table('virtual_classrooms', function (Blueprint $table) {
            $table->foreignId('course_class_id')->nullable()->after('dosen_id')->constrained('course_classes')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_classrooms', function (Blueprint $table) {
            $table->dropForeign(['course_class_id']);
            $table->dropColumn('course_class_id');
        });

        Schema::dropIfExists('course_class_students');
        Schema::dropIfExists('course_classes');
    }
};
