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
        Schema::create('hotspots', function (Blueprint $table) {
            $table->id();
            
            // API specific fields
            $table->string('hs_id')->nullable()->index(); // ID dari API
            $table->string('api_source')->default('indonesian_hotspot'); // Sumber API
            
            // Koordinat
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->point('coordinates')->nullable(); // For spatial queries
            
            // Data teknis
            $table->decimal('brightness', 8, 2)->nullable();
            $table->decimal('confidence', 5, 2)->nullable();
            $table->decimal('frp', 8, 2)->nullable(); // Fire Radiative Power
            
            // Waktu deteksi
            $table->date('acq_date');
            $table->time('acq_time');
            $table->timestamp('detection_datetime')->nullable();
            
            // Satelit
            $table->string('satellite')->nullable();
            $table->string('instrument')->default('MODIS');
            
            // Severity dan status
            $table->enum('severity', ['low', 'medium', 'high'])->default('low');
            $table->boolean('is_active')->default(true);
            
            // Data administratif Indonesia
            $table->string('provinsi')->nullable();
            $table->string('kabupaten')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('desa')->nullable();
            $table->string('kawasan')->nullable();
            $table->string('confidence_level')->nullable();
            
            // Metadata
            $table->json('raw_data')->nullable(); // Menyimpan data mentah dari API
            $table->timestamp('last_verified_at')->nullable();
            $table->boolean('is_processed')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['latitude', 'longitude']);
            $table->index(['acq_date', 'acq_time']);
            $table->index(['severity', 'is_active']);
            $table->index(['provinsi', 'kabupaten']);
            $table->index('detection_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hotspots');
    }
};
