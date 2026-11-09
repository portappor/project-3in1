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
        Schema::table('virtual_classrooms', function (Blueprint $table) {
            $table->string('presentation_type', 10)->default('pdf')->after('presentation_name');
            $table->json('presentation_data')->nullable()->after('presentation_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('virtual_classrooms', function (Blueprint $table) {
            $table->dropColumn(['presentation_type', 'presentation_data']);
        });
    }
};
