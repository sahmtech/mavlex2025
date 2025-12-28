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
        Schema::create('accounting_opening_balances', function (Blueprint $table) {
            $table->id();
            $table->date('year');
            $table->enum('type',['creditor', 'debtor']);
            $table->unsignedBigInteger('acc_transaction_id');
            
            $table->integer('business_id')->unsigned()->nullable();
            $table->integer('created_by')->unsigned();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('acc_transaction_id')->references('id')->on('accounting_accounts_transactions')->onDelete('cascade');

           
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
        Schema::dropIfExists('accounting_opening_balances');
    }
};