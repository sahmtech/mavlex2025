<?php

namespace Modules\Accounting\Http\Controllers;

use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Routing\Controller;
use Menu;

class DataController extends Controller
{
    /**
     * Superadmin package permissions
     *
     * @return array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'accounting_module',
                'label' => __('accounting::lang.accounting_module'),
                'default' => false,
            ],
        ];
    }

    /**
     * Adds cms menus
     *
     * @return null
     */
    public function modifyAdminMenu()
    {
        $business_id = session()->get('user.business_id');
        $module_util = new ModuleUtil();

        $is_accounting_enabled = (bool) $module_util->hasThePermissionInSubscription($business_id, 'accounting_module');

        $commonUtil = new Util();
        $is_admin = $commonUtil->is_admin(auth()->user(), $business_id);

        if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('accounting.access_accounting_module') && $is_accounting_enabled) {
            Menu::modify(
                'admin-sidebar-menu',
                function ($menu) {
                    $menu->dropdown(
                        __('accounting::lang.accounting'),

                        function ($sub) {
                            $sub->url(
                                action([\Modules\Accounting\Http\Controllers\AccountingController::class, 'dashboard']),
                                __('accounting::lang.accounting'),
                                ['icon' => '', 'active' => request()->segment(2) == 'dashboard']
                            );

                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.manage_accounts')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\CoaController::class, 'index']),
                                    __('accounting::lang.chart_of_accounts'),
                                    ['icon' => '', 'active' =>  request()->segment(2) == 'chart-of-accounts']
                                );
                            }

                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.cost_center')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\CostCenterController::class, 'index']),
                                    __('accounting::lang.cost_center'),
                                    ['icon' => '', 'active' =>  request()->segment(2) == 'cost_centers']
                                );
                            }
                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.opening_balances')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\OpeningBalanceController::class, 'index']),
                                    __('accounting::lang.opening_balances'),
                                    ['icon' => '', 'active' =>  request()->segment(2) == 'opening_balances']
                                );
                            }

                            if ((auth()->user()->can('superadmin')  || auth()->user()->can('accounting.receipt_vouchers'))) {

                            
                                 $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\ReceiptVouchersController::class, 'index']),
                                    __('accounting::lang.receipt_vouchers'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'receipt_vouchers']
                                );
                            }
                            if ((auth()->user()->can('superadmin')  || auth()->user()->can('accounting.payment_vouchers'))) {

                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\PaymentVouchersController::class, 'index']),
                                    __('accounting::lang.payment_vouchers'),
                                    ['icon' => '', 'active' => request()->segment(3) == 'payment_vouchers']
                                );
                            }
                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.journals')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'index']),
                                    __('accounting::lang.journal_entry'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'journal-entry']
                                );
                            }

                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.autoMigration')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\AutomatedMigrationController::class, 'index']),
                                    __('accounting::lang.automatedMigration'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'automated-migration']
                                );
                            }
                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.view_transfer')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\TransferController::class, 'index']),
                                    __('accounting::lang.transfer'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'transfer']
                                );
                            }
                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.transactions')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\TransactionController::class, 'index']),
                                    __('accounting::lang.transactions'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'transactions']
                                );
                            }
                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.manage_budget')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\BudgetController::class, 'index']),
                                    __('accounting::lang.budget'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'budget']
                                );
                            }
                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.view_reports')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\ReportController::class, 'index']),
                                    __('accounting::lang.reports'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'reports']
                                );
                            }
                            if (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || auth()->user()->can('accounting.settings')) {
                                $sub->url(
                                    action([\Modules\Accounting\Http\Controllers\SettingsController::class, 'index']),
                                    __('messages.settings'),
                                    ['icon' => '', 'active' => request()->segment(2) == 'settings']
                                );
                            }
                        },

                        ['icon' => 'fas fa-money-check fa', 'style' => config('app.env') == 'demo' ? 'background-color: #D483D9;' : '', 'active' => request()->segment(1) == 'accounting']
                    )->order(50);
                }
            );

            // action([\Modules\Accounting\Http\Controllers\AccountingController::class, 'dashboard'])  ,
        }
    }

    /**
     * Defines user permissions for the module.
     *
     * @return array
     */
    public function user_permissions()
    {
        return [
            [
                'value' => 'accounting.access_accounting_module',
                'label' => __('accounting::lang.access_accounting_module'),
                'default' => false,
            ],
            [
                'value' => 'accounting.manage_accounts',
                'label' => __('accounting::lang.manage_accounts'),
                'default' => false,
            ],
            [
                'value' => 'accounting.view_ledger',
                'label' => __('accounting::lang.view_ledger'),
                'default' => false,
            ],
            [
                'value' => 'accounting.edit_accounts',
                'label' => __('accounting::lang.edit_accounts'),
                'default' => false,
            ],
            [
                'value' => 'accounting.add_extra_accounts',
                'label' => __('accounting::lang.add_extra_accounts'),
                'default' => false,
            ],
            [
                'value' => 'accounting.active_accounts',
                'label' => __('accounting::lang.active_accounts'),
                'default' => false,
            ],
            [
                'value' => 'accouning.import_accounts',
                'label' => __('accounting::lang.importe_accounts'),
                'default' => false
            ],
            [
                'value' => 'accounting.cost_center',
                'label' => __('accounting::lang.cost_center'),
                'default' => false,
            ],
            [
                'value' => 'accounting.add_cost_center',
                'label' => __('accounting::lang.add_cost_center'),
                'default' => false,
            ],
            [
                'value' => 'accounting.costCenter.edit',
                'label' => __('accounting::lang.edit_cost_center'),
                'default' => false,
            ],
            [
                'value' => 'accounting.costCenter.delete',
                'label' => __('accounting::lang.delete_cost_center'),
                'default' => false,
            ],
            [
                'value' => 'accounting.opening_balances',
                'label' => __('accounting::lang.opening_balances'),
                'default' => false,
            ],
            [
                'value' => 'accounting.OpeningBalance.delete',
                'label' => __('accounting::lang.opening_balances_delete'),
                'default' => false,
            ],
            [
                'value' => 'accouning.import_opeining_balances',
                'label' => __('accounting::lang.importe_openingBalance'),
                'default' => false
            ],
            [
                'value' => 'accounting.receipt_vouchers',
                'label' => __('accounting::lang.receipt_vouchers'),
                'default' => false,
            ],
            [
                'value' => 'accounting.add_receipt_vouchers',
                'label' => __('accounting::lang.add_receipt_vouchers'),
                'default' => false,
            ],
            [
                'value' => 'accounting.print_receipt_vouchers',
                'label' => __('accounting::lang.print_receipt_vouchers'),
                'default' => false,
            ],
            [
                'value' => 'accounting.payment_vouchers',
                'label' => __('accounting::lang.payment_vouchers'),
                'default' => false,
            ],
            [
                'value' => 'accounting.add_payment_vouchers',
                'label' => __('accounting::lang.add_payment_vouchers'),
                'default' => false,
            ],
            [
                'value' => 'accounting.print_payment_vouchers',
                'label' => __('accounting::lang.print_payment_vouchers'),
                'default' => false,
            ],
            [
                'value' => 'accounting.journals',
                'label' => __('accounting::lang.journals'),
                'default' => false,
            ],
            [
                'value' => 'accounting.view_journal',
                'label' => __('accounting::lang.view_journal'),
                'default' => false,
            ],
            [
                'value' => 'accounting.add_journal',
                'label' => __('accounting::lang.add_journal'),
                'default' => false,
            ],
            [
                'value' => 'accounting.edit_journal',
                'label' => __('accounting::lang.edit_journal'),
                'default' => false,
            ],
            [
                'value' => 'accounting.delete_journal',
                'label' => __('accounting::lang.delete_journal'),
                'default' => false,
            ],
            [
                'value' => 'accounting.autoMigration',
                'label' => __('accounting::lang.autoMigration_page'),
                'default' => false,
            ],
            [
                'value' => 'accounting.index_autoMigration',
                'label' => __('accounting::lang.index_autoMigration'),
                'default' => false,
            ],
            [
                'value' => 'accounting.create_autoMigration',
                'label' => __('accounting::lang.create_autoMigration'),
                'default' => false,
            ],
            [
                'value' => 'accounting.edit_autoMigration',
                'label' => __('accounting::lang.edit_autoMigration'),
                'default' => false,
            ],
            [
                'value' => 'accounting.destroy_acc_trans_mapping_setting',
                'label' => __('accounting::lang.destroy_acc_trans_mapping_setting'),
                'default' => false,
            ],
            [
                'value' => 'accounting.view_transfer',
                'label' => __('accounting::lang.view_transfer'),
                'default' => false,
            ],
            [
                'value' => 'accounting.add_transfer',
                'label' => __('accounting::lang.add_transfer'),
                'default' => false,
            ],
            [
                'value' => 'accounting.edit_transfer',
                'label' => __('accounting::lang.edit_transfer'),
                'default' => false,
            ],
            [
                'value' => 'accounting.delete_transfer',
                'label' => __('accounting::lang.delete_transfer'),
                'default' => false,
            ],
            [
                'value' => 'accounting.transactions',
                'label' => __('accounting::lang.transactions'),
                'default' => false,
            ],
            [
                'value' => 'accounting.transaction_create_Journal_entry',
                'label' => __('accounting::lang.transaction_create_Journal_entry'),
                'default' => false,
            ],
            [
                'value' => 'accounting.manage_budget',
                'label' => __('accounting::lang.manage_budget'),
                'default' => false,
            ],
            [
                'value' => 'accounting.add_budget',
                'label' => __('accounting::lang.add_budget'),
                'default' => false,
            ],
            [
                'value' => 'accounting.view_reports',
                'label' => __('accounting::lang.view_reports'),
                'default' => false,
            ],
            [
                'value' => 'accounting.settings',
                'label' => __('accounting::lang.accounting_settings'),
                'default' => false,
            ],
            [
                'value' => 'accounting.rest_accounting_data',
                'label' => __('accounting::lang.rest_accounting_data'),
                'default' => false,
            ],





        ];
    }
}
