<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essentials_attendances', function (Blueprint $table) {
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_location');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_location');
            $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('essentials_attendances', function (Blueprint $table) {
            $table->dropColumn([
                'clock_in_latitude',
                'clock_in_longitude',
                'clock_out_latitude',
                'clock_out_longitude',
            ]);
        });
    }
};
