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
        Schema::table('sensors', function (Blueprint $table) {
            $table->string('external_device_id')->nullable()->after('id')->comment('External device ID for API integration');
            $table->unique('external_device_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensors', function (Blueprint $table) {
            $table->dropUnique(['external_device_id']);
            $table->dropColumn('external_device_id');
        });
    }
};
