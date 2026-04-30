<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essentials_shifts', function (Blueprint $table) {
            $table->boolean('allow_clock_outside_geofence')
                ->default(false)
                ->after('auto_clockout_time');
        });
    }

    public function down(): void
    {
        Schema::table('essentials_shifts', function (Blueprint $table) {
            $table->dropColumn('allow_clock_outside_geofence');
        });
    }
};
