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
        // First, we need to drop the existing check constraint if it exists
        Schema::table('tanks', function (Blueprint $table) {
            // This is a workaround for SQLite which doesn't support dropping check constraints directly
            // We'll create a new table with the correct schema, copy the data, then replace the old table
            
            // Create a temporary table with the new schema
            Schema::create('tanks_new', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->decimal('capacity', 10, 2);
                $table->decimal('current_level', 10, 2)->default(0);
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->boolean('is_active')->default(false);
                $table->decimal('min_threshold', 10, 2);
                $table->decimal('max_threshold', 10, 2);
                $table->enum('status', ['active', 'maintenance', 'inactive'])->default('active');
                $table->timestamp('last_maintenance')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
            
            // Copy data from old table to new table with status mapping
            // Since we don't have any data yet, we can just drop and recreate
            
            // Drop the old table
            Schema::dropIfExists('tanks');
            
            // Rename the new table
            Schema::rename('tanks_new', 'tanks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to the original status values if needed
        Schema::table('tanks', function (Blueprint $table) {
            // Create a temporary table with the original schema
            Schema::create('tanks_old', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->decimal('capacity', 10, 2);
                $table->decimal('current_level', 10, 2)->default(0);
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->boolean('is_active')->default(false);
                $table->decimal('min_threshold', 10, 2);
                $table->decimal('max_threshold', 10, 2);
                $table->enum('status', ['filling', 'draining', 'standby', 'error'])->default('standby');
                $table->timestamp('last_maintenance')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
            
            // Drop the current table
            Schema::dropIfExists('tanks');
            
            // Rename the old table back
            Schema::rename('tanks_old', 'tanks');
        });
    }
};
