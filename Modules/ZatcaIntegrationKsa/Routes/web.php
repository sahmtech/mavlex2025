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

Route::middleware('web', 'auth', 'language', 'AdminSidebarMenu')->prefix('zatca')->group(function () {
    Route::get('dashboard', [Modules\ZatcaIntegrationKsa\Http\Controllers\DashBoardController::class, 'index']);
    Route::get('onboarding', [Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'index']);

    Route::get('sales', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'salesList']);
    Route::get('sales-return', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'returnSalesList']);

    Route::get('sycs-sale/{id}', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'sycs_sale']);
    Route::get('sycs-return-sale/{id}', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'sync_sale_return']);
    Route::get('download-xml/{id}', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'downloadXml']);

    Route::get('sell-print-pdf/{id}', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'sale_print_pdf']);

    Route::get('sell-return-print-pdf/{id}', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'return_print_pdf']);

    Route::get('delete-testing-invoice', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'DeleteTestingInvoice']);

    Route::get('show-invoice-error/{id}', [Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'showInvoiceError']);


    Route::put('onboarding/{id}', [Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'update']);
    Route::post('zata-setting', [Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'zataSetting']);

    Route::post('pos-setting', [Modules\ZatcaIntegrationKsa\Http\Controllers\OnBoardingController::class, 'posBussinesUpdate']);

    Route::get('install', [\Modules\ZatcaIntegrationKsa\Http\Controllers\InstallController::class, 'index']);
    Route::post('install', [\Modules\ZatcaIntegrationKsa\Http\Controllers\InstallController::class, 'install']);
    Route::get('install/uninstall', [\Modules\ZatcaIntegrationKsa\Http\Controllers\InstallController::class, 'uninstall']);
    Route::get('install/update', [\Modules\ZatcaIntegrationKsa\Http\Controllers\InstallController::class, 'update']);
});

