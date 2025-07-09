<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void // Atau public function up() jika Anda menggunakan Laravel versi lama
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->nullable()->after('email'); // Sesuaikan tipe data dan posisi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void // Atau public function down() jika Anda menggunakan Laravel versi lama
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone_number');
        });
    }
};