<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEssentialsUserDevicesTable extends Migration
{
    /**
     * Run the migrations.
     * One registered device fingerprint per employee (user).
     *
     * @return void
     */
    public function up()
    {
        Schema::create('essentials_user_devices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->unique();
            $table->unsignedInteger('business_id')->index();
            $table->string('dev_name', 512)->nullable();
            $table->string('dev_number', 512)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('essentials_user_devices');
    }
}
