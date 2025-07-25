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
        Schema::create('pumps', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['running', 'stopped', 'error'])->default('stopped');
            $table->decimal('power_consumption', 10, 2)->default(0); // in watts
            $table->decimal('flow_rate', 10, 2)->default(0); // in liters/minute
            $table->integer('total_runtime')->default(0); // in minutes
            $table->timestamp('last_maintenance')->nullable();
            $table->string('error_code')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pumps');
    }
};
