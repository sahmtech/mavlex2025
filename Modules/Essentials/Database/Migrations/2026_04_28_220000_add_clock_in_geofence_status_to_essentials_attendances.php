<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClockInGeofenceStatusToEssentialsAttendances extends Migration
{
    /**
     * Run the migrations.
     * Values: na (no fence / no coords), inside, outside
     *
     * @return void
     */
    public function up()
    {
        Schema::table('essentials_attendances', function (Blueprint $table) {
            $table->string('clock_in_geofence_status', 16)->nullable()->after('clock_in_note');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('essentials_attendances', function (Blueprint $table) {
            $table->dropColumn('clock_in_geofence_status');
        });
    }
}
