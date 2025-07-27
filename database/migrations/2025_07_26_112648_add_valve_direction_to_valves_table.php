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
        Schema::table('valves', function (Blueprint $table) {
            $table->enum('valve_direction', ['inlet', 'outlet'])
                  ->nullable()
                  ->after('tank_id')
                  ->comment('For tank valves, indicates if it\'s an inlet or outlet valve');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valves', function (Blueprint $table) {
            $table->dropColumn('valve_direction');
        });
    }
};
