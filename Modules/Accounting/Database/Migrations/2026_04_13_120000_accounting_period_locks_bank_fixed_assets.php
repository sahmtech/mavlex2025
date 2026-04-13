<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounting_period_locks')) {
            Schema::create('accounting_period_locks', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id');
                $table->unsignedSmallInteger('lock_year');
                $table->unsignedTinyInteger('lock_month');
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'lock_year', 'lock_month'], 'accounting_period_locks_unique');
            });
        }

        if (! Schema::hasTable('accounting_bank_reconciliations')) {
            Schema::create('accounting_bank_reconciliations', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id');
                $table->unsignedBigInteger('accounting_account_id');
                $table->date('statement_date');
                $table->decimal('statement_balance', 22, 4);
                $table->decimal('book_balance', 22, 4)->nullable();
                $table->string('status', 32)->default('open');
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('accounting_bank_reconciliation_items')) {
            Schema::create('accounting_bank_reconciliation_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('reconciliation_id');
                // Short column name so MySQL never auto-builds an over-64-char index name
                $table->unsignedBigInteger('gl_line_id')->comment('accounting_accounts_transactions.id');
                $table->boolean('is_cleared')->default(false);
                $table->timestamps();
                $table->index('reconciliation_id', 'abri_recon_idx');
                $table->index('gl_line_id', 'abri_gl_line_idx');
            });
        }

        if (! Schema::hasTable('accounting_fixed_assets')) {
            Schema::create('accounting_fixed_assets', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id');
                $table->string('name');
                $table->date('acquisition_date');
                $table->decimal('cost', 22, 4);
                $table->decimal('salvage_value', 22, 4)->default(0);
                $table->unsignedInteger('useful_life_months');
                $table->unsignedBigInteger('asset_account_id');
                $table->unsignedBigInteger('accumulated_depreciation_account_id');
                $table->unsignedBigInteger('depreciation_expense_account_id');
                $table->string('status', 32)->default('active');
                $table->unsignedInteger('created_by');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('accounting_fixed_asset_depreciations')) {
            Schema::create('accounting_fixed_asset_depreciations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fixed_asset_id');
                $table->unsignedSmallInteger('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->decimal('amount', 22, 4);
                $table->unsignedBigInteger('acc_trans_mapping_id')->nullable();
                $table->timestamps();
                $table->unique(['fixed_asset_id', 'period_year', 'period_month'], 'fa_dep_unique');
            });
        }

        Schema::table('accounting_accounts_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('accounting_accounts_transactions', 'currency_id')) {
                $table->unsignedBigInteger('currency_id')->nullable()->after('note');
            }
            if (! Schema::hasColumn('accounting_accounts_transactions', 'exchange_rate')) {
                $table->decimal('exchange_rate', 20, 8)->nullable()->default(1)->after('currency_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('accounting_accounts_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('accounting_accounts_transactions', 'exchange_rate')) {
                $table->dropColumn('exchange_rate');
            }
            if (Schema::hasColumn('accounting_accounts_transactions', 'currency_id')) {
                $table->dropColumn('currency_id');
            }
        });

        Schema::dropIfExists('accounting_fixed_asset_depreciations');
        Schema::dropIfExists('accounting_fixed_assets');
        Schema::dropIfExists('accounting_bank_reconciliation_items');
        Schema::dropIfExists('accounting_bank_reconciliations');
        Schema::dropIfExists('accounting_period_locks');
    }
};
