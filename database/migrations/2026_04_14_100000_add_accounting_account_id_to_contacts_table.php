<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link contact (customer) to a chart-of-accounts ledger account (AR / sub-account).
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('contacts', 'accounting_account_id')) {
                $table->unsignedBigInteger('accounting_account_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'accounting_account_id')) {
                $table->dropColumn('accounting_account_id');
            }
        });
    }
};
