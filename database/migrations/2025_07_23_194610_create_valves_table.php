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
        Schema::create('valves', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['tank', 'plot', 'main']);
            $table->foreignId('tank_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('plot_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_open')->default(false);
            $table->decimal('flow_rate', 8, 2)->default(0); // in liters/minute
            $table->timestamp('last_actuated')->nullable();
            $table->enum('status', ['operational', 'stuck_open', 'stuck_closed', 'error'])->default('operational');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valves');
    }
};
