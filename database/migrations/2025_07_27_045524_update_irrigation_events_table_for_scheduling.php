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
        Schema::table('irrigation_events', function (Blueprint $table) {
            // Add fields for scheduling
            $table->string('recurrence_rule')->nullable()->after('trigger_type');
            $table->timestamp('recurrence_end_date')->nullable()->after('recurrence_rule');
            $table->unsignedBigInteger('parent_event_id')->nullable()->after('recurrence_end_date');
            $table->boolean('is_recurring')->default(false)->after('parent_event_id');
            
            // Add foreign key for recurring events
            $table->foreign('parent_event_id')
                  ->references('id')
                  ->on('irrigation_events')
                  ->onDelete('cascade');
            
            // Add index for better query performance
            $table->index(['is_recurring', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('irrigation_events', function (Blueprint $table) {
            $table->dropForeign(['parent_event_id']);
            $table->dropIndex(['is_recurring', 'start_time']);
            
            $table->dropColumn([
                'recurrence_rule',
                'recurrence_end_date',
                'parent_event_id',
                'is_recurring'
            ]);
        });
    }
};
