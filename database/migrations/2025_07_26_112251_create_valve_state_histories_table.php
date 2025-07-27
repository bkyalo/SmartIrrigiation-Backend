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
        Schema::create('valve_state_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('valve_id')->constrained()->onDelete('cascade');
            $table->boolean('is_open')->comment('True if valve was opened, false if closed');
            $table->decimal('flow_rate', 8, 2)->nullable()->comment('Flow rate at the time of state change (L/min)');
            $table->string('trigger_source')->default('manual')->comment('What triggered the state change (manual, schedule, api, system)');
            $table->text('notes')->nullable()->comment('Any additional notes about this state change');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->comment('User who triggered the change, if applicable');
            $table->json('metadata')->nullable()->comment('Additional metadata about the state change');
            $table->timestamps();
            
            // Indexes for faster lookups
            $table->index(['valve_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valve_state_histories');
    }
};
