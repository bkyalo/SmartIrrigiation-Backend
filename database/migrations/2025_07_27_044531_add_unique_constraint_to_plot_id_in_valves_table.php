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
        // First, remove any duplicate plot_id values
        \DB::statement('DELETE v1 FROM valves v1
            INNER JOIN valves v2 
            WHERE v1.id < v2.id 
            AND v1.plot_id = v2.plot_id 
            AND v1.plot_id IS NOT NULL');

        // Then add the unique constraint
        Schema::table('valves', function (Blueprint $table) {
            $table->unique('plot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valves', function (Blueprint $table) {
            $table->dropUnique(['plot_id']);
        });
    }
};
