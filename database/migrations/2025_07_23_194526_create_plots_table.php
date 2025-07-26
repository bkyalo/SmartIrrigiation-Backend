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
        Schema::create('plots', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('area', 10, 2)->nullable(); // in square meters
            $table->string('crop_type')->nullable();
            $table->string('soil_type')->nullable();
            $table->decimal('moisture_threshold', 5, 2)->nullable();
            $table->integer('irrigation_duration')->default(30); // in minutes
            $table->enum('status', ['idle', 'irrigating', 'scheduled', 'error'])->default('idle');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            // Removed polygon column as it requires spatial database support
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plots');
    }
};
