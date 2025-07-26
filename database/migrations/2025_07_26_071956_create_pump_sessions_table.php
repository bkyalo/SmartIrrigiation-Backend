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
        Schema::create('pump_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pump_id')->constrained()->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('stopped_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->timestamps();
            
            $table->index('pump_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pump_sessions');
    }
};
