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
        Schema::create('accounting_acc_trans_mapping_setting_auto_migrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('business_id');
            $table->bigInteger('mapping_setting_id');
            $table->bigInteger('accounting_account_id');

            $table->string('ref_no', 100);
            $table->string('sub_type', 100)->comment('EX:journal_entry');
            $table->enum('type', ['debit', 'credit']);
            $table->integer('created_by');
            $table->integer('journal_entry_number');

            $table->enum('amount', ['final_total', 'total_before_tax', 'tax_amount', 'shipping_charges', 'discount_amount']);
            $table->dateTime('operation_date');

            // $table->foreign('mapping_setting_id')->references('id')->cascadeOnDelete('accounting_mapping_setting_tests'); 
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
        Schema::dropIfExists('accounting_acc_trans_mapping_setting_auto_migrations');
    }
};