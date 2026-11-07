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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('mahasiswa')->after('email');
            $table->string('prodi')->nullable()->after('role');
            $table->string('fakultas')->nullable()->after('prodi');
            $table->boolean('status_aktif')->default(true)->after('fakultas');
            $table->timestamp('password_changed_at')->nullable()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'role',
                'prodi',
                'fakultas',
                'status_aktif',
                'password_changed_at',
            ]);
        });
    }
};

