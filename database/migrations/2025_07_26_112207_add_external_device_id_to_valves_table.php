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
            $table->string('external_device_id')->nullable()->after('id')->comment('External device identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('valves', function (Blueprint $table) {
            $table->dropColumn('external_device_id');
        });
    }
};
