<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_mapping_setting_auto_migrations', function (Blueprint $table) {
            $table->integer('business_id')->unsigned()->after('id');
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_mapping_setting_auto_migrations', function (Blueprint $table) {

        });
    }
};