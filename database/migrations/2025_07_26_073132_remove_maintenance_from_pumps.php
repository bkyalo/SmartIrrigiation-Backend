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
        Schema::table('pumps', function (Blueprint $table) {
            // Remove maintenance-related columns
            $table->dropColumn('last_maintenance');
        });
        
        // Update the status enum to remove 'maintenance' as a valid status
        \DB::statement("ALTER TABLE pumps MODIFY status ENUM('running', 'stopped', 'error') NOT NULL DEFAULT 'stopped'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pumps', function (Blueprint $table) {
            // Add back the last_maintenance column
            $table->timestamp('last_maintenance')->nullable()->after('total_runtime');
        });
        
        // Revert the status enum to include 'maintenance'
        \DB::statement("ALTER TABLE pumps MODIFY status ENUM('running', 'stopped', 'maintenance', 'error') NOT NULL DEFAULT 'stopped'");
    }
};
