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
        Schema::create('irrigation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plot_id')->constrained()->onDelete('cascade');
            $table->foreignId('valve_id')->constrained()->onDelete('cascade');
            $table->foreignId('pump_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();
            $table->decimal('volume_used', 10, 2)->default(0); // in liters
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'failed', 'cancelled'])->default('scheduled');
            $table->enum('trigger_type', ['manual', 'schedule', 'auto', 'ai']);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for faster queries
            $table->index('start_time');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('irrigation_events');
    }
};
