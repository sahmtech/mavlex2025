<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Modules\Accounting\Http\Controllers\BankReconciliationController;
use Modules\Accounting\Http\Controllers\FixedAssetController;
use Modules\Accounting\Http\Controllers\OpeningBalanceController;
use Modules\Accounting\Http\Controllers\PaymentVouchersController;
use Modules\Accounting\Http\Controllers\PeriodLockController;
use Modules\Accounting\Http\Controllers\ReceiptVouchersController;

Route::middleware('web', 'SetSessionData', 'auth', 'language', 'timezone', 'AdminSidebarMenu')->prefix('accounting')->group(function () {
    Route::get('dashboard', [\Modules\Accounting\Http\Controllers\AccountingController::class, 'dashboard']);

    Route::get('accounts-dropdown', [\Modules\Accounting\Http\Controllers\AccountingController::class, 'AccountsDropdown'])->name('accounts-dropdown');

    Route::get('open-create-dialog/{id}', [\Modules\Accounting\Http\Controllers\CoaController::class, 'open_create_dialog'])->name('open_create_dialog');
    Route::get('get-account-sub-types', [\Modules\Accounting\Http\Controllers\CoaController::class, 'getAccountSubTypes']);
    Route::get('get-account-details-types', [\Modules\Accounting\Http\Controllers\CoaController::class, 'getAccountDetailsType']);
    Route::resource('chart-of-accounts', \Modules\Accounting\Http\Controllers\CoaController::class);
    Route::get('ledger/{id}', [\Modules\Accounting\Http\Controllers\CoaController::class, 'ledger'])->name('accounting.ledger');
    Route::get('activate-deactivate/{id}', [\Modules\Accounting\Http\Controllers\CoaController::class, 'activateDeactivate']);
    Route::get('create-default-accounts', [\Modules\Accounting\Http\Controllers\CoaController::class, 'createDefaultAccounts'])->name('accounting.create-default-accounts');
    Route::get('importe-accounts', [\Modules\Accounting\Http\Controllers\CoaController::class, 'viewImporte_accounts'] )->name('accounting.viewImporte_accounts');
    Route::post('save-importe-accounts',  [\Modules\Accounting\Http\Controllers\CoaController::class, 'importe_accounts'])->name('accounting.saveImporte_accounts');
    Route::get('ledger/print/{id}',[\Modules\Accounting\Http\Controllers\CoaController::class, 'ledgerPrint'] );

    Route::resource('journal-entry', \Modules\Accounting\Http\Controllers\JournalEntryController::class);
    Route::get('journal-entry/history/{id}', [ \Modules\Accounting\Http\Controllers\JournalEntryController::class,'history_index']);
    Route::get('journal-entry/history-view/{id}',[ \Modules\Accounting\Http\Controllers\JournalEntryController::class,'history_view']);
    Route::get('journal-entry/print/{id}',[ \Modules\Accounting\Http\Controllers\JournalEntryController::class,'print'] );
    

    Route::resource('automated-migration', \Modules\Accounting\Http\Controllers\AutomatedMigrationController::class);
    Route::get('automated-migration-delete-dialog/{id}', [\Modules\Accounting\Http\Controllers\AutomatedMigrationController::class, 'delete_dialog']);
    Route::get('automated-migration-active-toggle/{id}', [\Modules\Accounting\Http\Controllers\AutomatedMigrationController::class, 'active_toggle']);
    Route::get('automated-migration-delete-acc-trans-mapping/{id}', [\Modules\Accounting\Http\Controllers\AutomatedMigrationController::class, 'destroy_acc_trans_mapping_setting']);
    Route::post('store-deflute-auto-migration', [\Modules\Accounting\Http\Controllers\AutomatedMigrationController::class, 'store_deflute_auto_migration'])->name('store_deflute_auto_migration');
    Route::get('create-deflute-auto-migration', [\Modules\Accounting\Http\Controllers\AutomatedMigrationController::class, 'create_deflute_auto_migration'])->name('create_deflute_auto_migration');


    Route::get('settings', [\Modules\Accounting\Http\Controllers\SettingsController::class, 'index']);
    Route::get('reset-data', [\Modules\Accounting\Http\Controllers\SettingsController::class, 'resetData']);

    Route::resource('account-type', \Modules\Accounting\Http\Controllers\AccountTypeController::class);

    Route::resource('transfer', \Modules\Accounting\Http\Controllers\TransferController::class)->except(['show']);

    Route::resource('budget', \Modules\Accounting\Http\Controllers\BudgetController::class)->except(['show', 'edit', 'update', 'destroy']);

    Route::get('reports', [\Modules\Accounting\Http\Controllers\ReportController::class, 'index']);
    Route::get('reports/trial-balance', [\Modules\Accounting\Http\Controllers\ReportController::class, 'trialBalance'])->name('accounting.trialBalance');
    Route::get('reports/balance-sheet', [\Modules\Accounting\Http\Controllers\ReportController::class, 'balanceSheet'])->name('accounting.balanceSheet');
    Route::get('reports/customers-suppliers-statement/{id}', [\Modules\Accounting\Http\Controllers\ReportController::class, 'customersSuppliersStatement'])->name('accounting.customersSuppliersStatement');
    Route::get('reports/income-statement',[\Modules\Accounting\Http\Controllers\ReportController::class, 'incomeStatement'] )->name('accounting.incomeStatement');
    
    Route::get(
        'reports/account-receivable-ageing-report',
        [\Modules\Accounting\Http\Controllers\ReportController::class, 'accountReceivableAgeingReport']
    )->name('accounting.account_receivable_ageing_report');
    Route::get(
        'reports/account-receivable-ageing-details',
        [\Modules\Accounting\Http\Controllers\ReportController::class, 'accountReceivableAgeingDetails']
    )->name('accounting.account_receivable_ageing_details');

    Route::get(
        'reports/account-payable-ageing-report',
        [\Modules\Accounting\Http\Controllers\ReportController::class, 'accountPayableAgeingReport']
    )->name('accounting.account_payable_ageing_report');
    Route::get(
        'reports/account-payable-ageing-details',
        [\Modules\Accounting\Http\Controllers\ReportController::class, 'accountPayableAgeingDetails']
    )->name('accounting.account_payable_ageing_details');


    Route::resource('cost_centers', \Modules\Accounting\Http\Controllers\CostCenterController::class);
    Route::put('cost-center-update', [\Modules\Accounting\Http\Controllers\CostCenterController::class, 'update'])->name('cost_center_update');
    Route::post('cost-center-store', [\Modules\Accounting\Http\Controllers\CostCenterController::class, 'store'])->name('cost_center_store');


    Route::resource('opening_balances', OpeningBalanceController::class);
    Route::get('/accounting/opening_balance/equation', [OpeningBalanceController::class, 'calcEquation'])->name('opening_balance.calc');
    Route::get('/opening-balance/importe', [OpeningBalanceController::class, 'viewImporte_openingBalance'])->name('viewImporte_openingBalance');
    Route::post('/opening-balance/save-importe', [OpeningBalanceController::class, 'importe_openingBalance'])->name('save-importe_openingBalance');

    Route::resource('payment_vouchers', PaymentVouchersController::class);
    Route::get('/accounting/payment_vouchers', [PaymentVouchersController::class, 'index'])->name('index-payment_vouchers');
    Route::post('/accounting/payment_vouchers', [PaymentVouchersController::class, 'store'])->name('index-store');
    Route::get('/accounting/payment_vouchers/load/data', [PaymentVouchersController::class, 'loadNeededData'])->name('payment_vouchers.load');

    Route::resource('receipt_vouchers', ReceiptVouchersController::class);
    Route::get('/accounting/receipt_vouchers/load/data', [ReceiptVouchersController::class, 'loadNeededData'])->name('receipt_vouchers.load');

   
    Route::get('transactions', [\Modules\Accounting\Http\Controllers\TransactionController::class, 'index']);
    Route::get('transactions/map', [\Modules\Accounting\Http\Controllers\TransactionController::class, 'map']);
    Route::get('transactions/ctrate-Journal-entry/{id}', [\Modules\Accounting\Http\Controllers\TransactionController::class, 'create_Journal_entry'])->name('create_Journal_entry');
    Route::post('transactions/save-map', [\Modules\Accounting\Http\Controllers\TransactionController::class, 'saveMap']);
    Route::post('save-settings', [\Modules\Accounting\Http\Controllers\SettingsController::class, 'saveSettings']);

    Route::get('install', [\Modules\Accounting\Http\Controllers\InstallController::class, 'index']);
    Route::post('install', [\Modules\Accounting\Http\Controllers\InstallController::class, 'install']);
    Route::get('install/uninstall', [\Modules\Accounting\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [\Modules\Accounting\Http\Controllers\InstallController::class, 'update']);

    Route::get('period-locks', [PeriodLockController::class, 'index'])->name('accounting.period-locks.index');
    Route::post('period-locks', [PeriodLockController::class, 'store'])->name('accounting.period-locks.store');
    Route::delete('period-locks/{id}', [PeriodLockController::class, 'destroy'])->name('accounting.period-locks.destroy');

    Route::get('bank-reconciliation', [BankReconciliationController::class, 'index'])->name('accounting.bank-reconciliation.index');
    Route::get('bank-reconciliation/create', [BankReconciliationController::class, 'create'])->name('accounting.bank-reconciliation.create');
    Route::post('bank-reconciliation', [BankReconciliationController::class, 'store'])->name('accounting.bank-reconciliation.store');
    Route::get('bank-reconciliation/{id}', [BankReconciliationController::class, 'show'])->name('accounting.bank-reconciliation.show');
    Route::post('bank-reconciliation/{id}/items', [BankReconciliationController::class, 'updateItems'])->name('accounting.bank-reconciliation.items');
    Route::post('bank-reconciliation/{id}/complete', [BankReconciliationController::class, 'complete'])->name('accounting.bank-reconciliation.complete');

    Route::get('fixed-assets', [FixedAssetController::class, 'index'])->name('accounting.fixed-assets.index');
    Route::get('fixed-assets/create', [FixedAssetController::class, 'create'])->name('accounting.fixed-assets.create');
    Route::post('fixed-assets', [FixedAssetController::class, 'store'])->name('accounting.fixed-assets.store');
    Route::post('fixed-assets/{id}/depreciate', [FixedAssetController::class, 'depreciate'])->name('accounting.fixed-assets.depreciate');
});