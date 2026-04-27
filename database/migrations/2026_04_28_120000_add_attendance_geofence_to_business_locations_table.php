<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clock-in geofence per branch: center point + radius (meters). Optional; used when enabled.
     */
    public function up(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            $table->boolean('attendance_geofence_enabled')->default(false);
            $table->decimal('attendance_geofence_latitude', 10, 7)->nullable();
            $table->decimal('attendance_geofence_longitude', 10, 7)->nullable();
            $table->unsignedInteger('attendance_geofence_radius_meters')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            $table->dropColumn([
                'attendance_geofence_enabled',
                'attendance_geofence_latitude',
                'attendance_geofence_longitude',
                'attendance_geofence_radius_meters',
            ]);
        });
    }
};
