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
        Schema::create('sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['water_level', 'soil_moisture', 'temperature', 'humidity', 'flow']);
            $table->string('location_type'); // tank, plot, environment
            $table->unsignedBigInteger('location_id');
            $table->integer('reading_interval')->default(300); // in seconds
            $table->timestamp('last_reading_at')->nullable();
            $table->enum('status', ['active', 'inactive', 'error'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensors');
    }
};
