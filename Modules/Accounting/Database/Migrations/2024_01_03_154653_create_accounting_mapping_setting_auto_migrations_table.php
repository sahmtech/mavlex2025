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
        Schema::create('accounting_mapping_setting_auto_migrations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['sell', 'sell_return', 'opening_stock', 'purchase', 'purchase_order', 'purchase_return', 'expense', 'sell_transfer', 'purchase_transfer', 'payroll', 'opening_balance', 'other']);
            $table->string('name');
            $table->string('status');
            $table->boolean('active')->default(true);
            $table->enum('payment_status', ['paid', 'due', 'partial']);
            $table->enum('method', ['cash', 'card', 'cheque', 'bank_transfer', 'other']);
            $table->integer('created_by');
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
        Schema::dropIfExists('accounting_mapping_setting_auto_migrations');
    }
};