<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('essentials_attendances', function (Blueprint $table) {
            $table->string('clock_in_image', 500)->nullable()->after('clock_in_location');
        });
    }

    public function down(): void
    {
        Schema::table('essentials_attendances', function (Blueprint $table) {
            $table->dropColumn('clock_in_image');
        });
    }
};
