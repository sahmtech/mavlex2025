<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essentials_attendances', function (Blueprint $table) {
            $table->string('clock_out_geofence_status', 16)->nullable()->after('clock_out_note');
        });
    }

    public function down(): void
    {
        Schema::table('essentials_attendances', function (Blueprint $table) {
            $table->dropColumn('clock_out_geofence_status');
        });
    }
};
