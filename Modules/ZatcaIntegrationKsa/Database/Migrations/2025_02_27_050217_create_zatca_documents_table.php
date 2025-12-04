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
        Schema::create('zatca_documents', function (Blueprint $table) {
            $table->id();
            $table->string('icv');
            $table->uuid('uuid')->unique();
            $table->string('hash')->nullable(); // Response
            $table->longText('xml')->nullable(); // Response
            $table->boolean('sent_to_zatca')->nullable(); // Response
            $table->string('sent_to_zatca_status')->nullable(); // Response
            $table->dateTime('signing_time')->nullable(); // Response
            $table->longText('response')->nullable(); // Response
            $table->string('type')->nullable();
            $table->string('portal_mode')->nullable();
            $table->foreignId('transaction_id'); // Local invoice ID
            $table->foreignId('location_id');
            $table->foreignId('business_id');
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
        Schema::dropIfExists('zatca_documents');
    }
};
