<?php

namespace Modules\Accounting\Utils;

use App\Business;
use App\Transaction;
use App\TransactionPayment;
use App\Utils\Util;
use Carbon\Carbon;
use DB;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccountType;
use Modules\Accounting\Entities\AccountingMappingSettingAutoMigration;

class AccountingUtil extends Util
{
    public function balanceFormula(
        $accounting_accounts_alias = 'accounting_accounts',
        $accounting_account_transaction_alias = 'AAT'
    ) {
        return "SUM( IF(
            ($accounting_accounts_alias.account_primary_type='asset' AND $accounting_account_transaction_alias.type='debit')
            OR ($accounting_accounts_alias.account_primary_type='expense' AND $accounting_account_transaction_alias.type='debit')
            OR ($accounting_accounts_alias.account_primary_type='income' AND $accounting_account_transaction_alias.type='credit')
            OR ($accounting_accounts_alias.account_primary_type='equity' AND $accounting_account_transaction_alias.type='credit')
            OR ($accounting_accounts_alias.account_primary_type='liability' AND $accounting_account_transaction_alias.type='credit'), 
            amount, -1*amount)) as balance";
    }

    public function getAccountingSettings($business_id)
    {
        $accounting_settings = Business::where('id', $business_id)
            ->value('accounting_settings');

        $accounting_settings = !empty($accounting_settings) ? json_decode($accounting_settings, true) : [];

        return $accounting_settings;
    }

    public function getAgeingReport($business_id, $type, $group_by, $location_id = null)
    {
        $today = \Carbon::now()->format('Y-m-d');
        $query = Transaction::where('transactions.business_id', $business_id);

        if ($type == 'sell') {
            $query->where('transactions.type', 'sell')
                ->where('transactions.status', 'final');
        } elseif ($type == 'purchase') {
            $query->where('transactions.type', 'purchase')
                ->where('transactions.status', 'received');
        }

        if (!empty($location_id)) {
            $query->where('transactions.location_id', $location_id);
        }

        $dues = $query->whereNotNull('transactions.pay_term_number')
            ->whereIn('transactions.payment_status', ['partial', 'due'])
            ->join('contacts as c', 'c.id', '=', 'transactions.contact_id')
            ->select(
                DB::raw(
                    'DATEDIFF(
                            "' . $today . '", 
                            IF(
                                transactions.pay_term_type="days",
                                DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY),
                                DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH)
                            )
                        ) as diff'
                ),
                DB::raw('SUM(transactions.final_total - 
                        (SELECT COALESCE(SUM(IF(tp.is_return = 1, -1*tp.amount, tp.amount)), 0) 
                        FROM transaction_payments as tp WHERE tp.transaction_id = transactions.id) )  
                        as total_due'),

                'c.name as contact_name',
                'transactions.contact_id',
                'transactions.invoice_no',
                'transactions.ref_no',
                'transactions.transaction_date',
                DB::raw('IF(
                        transactions.pay_term_type="days",
                        DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number DAY),
                        DATE_ADD(transactions.transaction_date, INTERVAL transactions.pay_term_number MONTH)
                    ) as due_date')
            )
            ->groupBy('transactions.id')
            ->get();

        $report_details = [];
        if ($group_by == 'contact') {
            foreach ($dues as $due) {
                if (!isset($report_details[$due->contact_id])) {
                    $report_details[$due->contact_id] = [
                        'name' => $due->contact_name,
                        '<1' => 0,
                        '1_30' => 0,
                        '31_60' => 0,
                        '61_90' => 0,
                        '>90' => 0,
                        'total_due' => 0,
                    ];
                }

                if ($due->diff < 1) {
                    $report_details[$due->contact_id]['<1'] += $due->total_due;
                } elseif ($due->diff >= 1 && $due->diff <= 30) {
                    $report_details[$due->contact_id]['1_30'] += $due->total_due;
                } elseif ($due->diff >= 31 && $due->diff <= 60) {
                    $report_details[$due->contact_id]['31_60'] += $due->total_due;
                } elseif ($due->diff >= 61 && $due->diff <= 90) {
                    $report_details[$due->contact_id]['61_90'] += $due->total_due;
                } elseif ($due->diff > 90) {
                    $report_details[$due->contact_id]['>90'] += $due->total_due;
                }

                $report_details[$due->contact_id]['total_due'] += $due->total_due;
            }
        } elseif ($group_by == 'due_date') {
            $report_details = [
                'current' => [],
                '1_30' => [],
                '31_60' => [],
                '61_90' => [],
                '>90' => [],
            ];
            foreach ($dues as $due) {
                $temp_array = [
                    'transaction_date' => $this->format_date($due->transaction_date),
                    'due_date' => $this->format_date($due->due_date),
                    'ref_no' => $due->ref_no,
                    'invoice_no' => $due->invoice_no,
                    'contact_name' => $due->contact_name,
                    'due' => $due->total_due,
                ];
                if ($due->diff < 1) {
                    $report_details['current'][] = $temp_array;
                } elseif ($due->diff >= 1 && $due->diff <= 30) {
                    $report_details['1_30'][] = $temp_array;
                } elseif ($due->diff >= 31 && $due->diff <= 60) {
                    $report_details['31_60'][] = $temp_array;
                } elseif ($due->diff >= 61 && $due->diff <= 90) {
                    $report_details['61_90'][] = $temp_array;
                } elseif ($due->diff > 90) {
                    $report_details['>90'][] = $temp_array;
                }
            }
        }

        return $report_details;
    }

    /**
     * Function to delete a mapping
     */
    public function deleteMap($transaction_id, $transaction_payment_id)
    {
        AccountingAccountsTransaction::where('transaction_id', $transaction_id)
            ->whereIn('map_type', ['payment_account', 'deposit_to'])
            ->where('transaction_payment_id', $transaction_payment_id)
            ->delete();
    }

    /**
     * Function to save a mapping
     */
    public function saveMap($type, $id, $user_id, $business_id, $deposit_to, $payment_account)
    {
        if ($type == 'sell') {
            $transaction = Transaction::where('business_id', $business_id)->where('id', $id)->firstorFail();

            //$payment_account will increase = sales = credit
            $payment_data = [
                'accounting_account_id' => $payment_account,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'credit',
                'sub_type' => $type,
                'map_type' => 'payment_account',
                'created_by' => $user_id,
                'operation_date' => \Carbon::now(),
            ];

            //Deposit to will increase = debit
            $deposit_data = [
                'accounting_account_id' => $deposit_to,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'debit',
                'sub_type' => $type,
                'map_type' => 'deposit_to',
                'created_by' => $user_id,
                'operation_date' => \Carbon::now(),
            ];
        } elseif (in_array($type, ['purchase_payment', 'sell_payment'])) {
            $transaction_payment = TransactionPayment::where('id', $id)->where('business_id', $business_id)
                ->firstorFail();

            //$payment_account will increase = sales = credit
            $payment_data = [
                'accounting_account_id' => $payment_account,
                'transaction_id' => null,
                'transaction_payment_id' => $id,
                'amount' => $transaction_payment->amount,
                'type' => 'credit',
                'sub_type' => $type,
                'map_type' => 'payment_account',
                'created_by' => $user_id,
                'operation_date' => \Carbon::now(),
            ];

            //Deposit to will increase = debit
            $deposit_data = [
                'accounting_account_id' => $deposit_to,
                'transaction_id' => null,
                'transaction_payment_id' => $id,
                'amount' => $transaction_payment->amount,
                'type' => 'debit',
                'sub_type' => $type,
                'map_type' => 'deposit_to',
                'created_by' => $user_id,
                'operation_date' => \Carbon::now(),
            ];
        } elseif ($type == 'purchase') {
            $transaction = Transaction::where('business_id', $business_id)->where('id', $id)->firstorFail();

            //$payment_account will increase = sales = credit
            $payment_data = [
                'accounting_account_id' => $payment_account,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'credit',
                'sub_type' => $type,
                'map_type' => 'payment_account',
                'created_by' => $user_id,
                'operation_date' => \Carbon::now(),
            ];

            //Deposit to will increase = debit
            $deposit_data = [
                'accounting_account_id' => $deposit_to,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'debit',
                'sub_type' => $type,
                'map_type' => 'deposit_to',
                'created_by' => $user_id,
                'operation_date' => \Carbon::now(),
            ];
        } elseif ($type == 'expense') {
            $transaction = Transaction::where('business_id', $business_id)->where('id', $id)->firstorFail();
            $payment_data = [
                'accounting_account_id' => $payment_account,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'credit',
                'sub_type' => $type,
                'map_type' => 'payment_account',
                'created_by' => $user_id,
                'operation_date' => \Carbon::now(),
            ];

            $deposit_data = [
                'accounting_account_id' => $deposit_to,
                'transaction_id' => $id,
                'transaction_payment_id' => null,
                'amount' => $transaction->final_total,
                'type' => 'debit',
                'sub_type' => $type,
                'map_type' => 'deposit_to',
                'created_by' => $user_id,
                'operation_date' => \Carbon::now(),
            ];
        }

        AccountingAccountsTransaction::updateOrCreateMapTransaction($payment_data);
        AccountingAccountsTransaction::updateOrCreateMapTransaction($deposit_data);
    }

    public static function next_GLC($parent_account_id, $business_id)
    {



        // parent_account_id
        $last_parent_account = AccountingAccount::where([['parent_account_id', '=', $parent_account_id], ['business_id', '=', $business_id]])->latest()->first();


        if ($last_parent_account) {


            $last_code = $last_parent_account ? substr($last_parent_account->gl_code, -strlen($last_parent_account->gl_code)) : "00";

            $next_code = str_pad((int)$last_code + 1, strlen($last_parent_account->gl_code), "0", STR_PAD_LEFT);
            return $next_code;
        }

        $parent_account = AccountingAccount::find($parent_account_id);
        $last_code = substr($parent_account->gl_code, -strlen($parent_account->gl_code));

        //  $nextNumeric = substr($last_code, -1) + 1;
        $next_code = $last_code . '01';


        return $next_code;
    }

    public static function Default_Accounts($business_id, $user_id)
{
    return array(
        0 => array(
            'name' => 'حسابات الدفع (الدائنون)',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 5,
            'detail_type_id' => 57,
            'gl_code' => 4101,
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        1 => array(
            'name' => 'بطاقات ائتمان',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 6,
            'detail_type_id' => 58,
            'status' => 'active',
            'gl_code' => 4201,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        2 => array(
            'name' => 'مصروفات الأجور',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 139,
            'status' => 'active',
            'gl_code' => 220231,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        3 => array(
            'name' => 'مرافق (كهرباء، ماء، إلخ)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 148,
            'status' => 'active',
            'gl_code' => 220230,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        4 => array(
            'name' => 'خسائر غير محققة على الأوراق المالية صافي الضريبة',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 11,
            'detail_type_id' => 112,
            'status' => 'active',
            'gl_code' => 3205,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        5 => array(
            'name' => 'أموال غير مودعة',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 1,
            'detail_type_id' => 28,
            'gl_code' => '1108',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        6 => array(
            'name' => 'إيرادات غير مصنفة',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 10,
            'detail_type_id' => 102,
            'status' => 'active',
            'gl_code' => 3108,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        7 => array(
            'name' => 'مصروفات غير مصنفة',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 137,
            'status' => 'active',
            'gl_code' => 22029,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        8 => array(
            'name' => 'أصول غير مصنفة',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 1,
            'detail_type_id' => 25,
            'gl_code' => '1107',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        9 => array(
            'name' => 'إيرادات مدفوعات نقدية غير مطبقة',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 10,
            'detail_type_id' => 104,
            'status' => 'active',
            'gl_code' => 3107,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        10 => array(
            'name' => 'مصروفات سفر (مبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => '146',
            'status' => 'active',
            'gl_code' => 22028,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        11 => array(
            'name' => 'مصروفات سفر (إدارية)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => '145',
            'status' => 'active',
            'gl_code' => 22027,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        12 => array(
            'name' => 'لوازم مكتبية',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 144,
            'status' => 'active',
            'gl_code' => 22026,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        13 => array(
            'name' => 'المقاولون من الباطن (تكلفة المبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '113',
            'status' => 'active',
            'gl_code' => 2109,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        14 => array(
            'name' => 'قرطاسية وطباعة',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => '136',
            'status' => 'active',
            'gl_code' => 22025,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        15 => array(
            'name' => 'دين قصير الأجل',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 7,
            'detail_type_id' => 68,
            'status' => 'active',
            'gl_code' => 4306,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        16 => array(
            'name' => 'مصروفات شحن وتوصيل',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 142,
            'status' => 'active',
            'gl_code' => 22024,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        17 => array(
            'name' => 'إيرادات مبيعات المنتجات',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 10,
            'detail_type_id' => 102,
            'status' => 'active',
            'gl_code' => 3106,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        18 => array(
            'name' => 'مبيعات بالجملة',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 10,
            'detail_type_id' => '101',
            'status' => 'active',
            'gl_code' => 3105,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        19 => array(
            'name' => 'مبيعات تجزئة',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 10,
            'detail_type_id' => '100',
            'status' => 'active',
            'gl_code' => 3104,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        20 => array(
            'name' => 'المبيعات',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 10,
            'detail_type_id' => 102,
            'status' => 'active',
            'gl_code' => 3103,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        21 => array(
            'name' => 'إيرادات عامة',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 10,
            'detail_type_id' => '99',
            'status' => 'active',
            'gl_code' => 3102,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        22 => array(
            'name' => 'إصلاح وصيانة',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 141,
            'status' => 'active',
            'gl_code' => 22023,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        23 => array(
            'name' => 'مدفوعات إيجار أو إيجار تشغيلي',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 140,
            'status' => 'active',
            'gl_code' => 22022,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        24 => array(
            'name' => 'فروق التسوية',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 14,
            'detail_type_id' => 152,
            'status' => 'active',
            'gl_code' => 2301,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        25 => array(
            'name' => 'المشتريات',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 143,
            'status' => 'active',
            'gl_code' => 22021,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        26 => array(
            'name' => 'الممتلكات والآلات والمعدات',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 3,
            'detail_type_id' => 41,
            'gl_code' => '1302',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        27 => array(
            'name' => 'مصروفات مدفوعة مقدماً',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 1,
            'detail_type_id' => 26,
            'gl_code' => '1106',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        28 => array(
            'name' => 'التزامات كشوف المرتبات',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 7,
            'detail_type_id' => 70,
            'status' => 'active',
            'gl_code' => 4305,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        29 => array(
            'name' => 'مصروفات الرواتب',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 139,
            'status' => 'active',
            'gl_code' => 22020,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        30 => array(
            'name' => 'تسوية الرواتب',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 7,
            'detail_type_id' => 69,
            'status' => 'active',
            'gl_code' => 4304,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        31 => array(
            'name' => 'مصاريف عامة (تكلفة المبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '113',
            'status' => 'active',
            'gl_code' => 2108,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        32 => array(
            'name' => 'مصروفات إعلانات',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => '118',
            'status' => 'active',
            'gl_code' => 22017,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        33 => array(
            'name' => 'مصروفات بيع أخرى',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 138,
            'status' => 'active',
            'gl_code' => 22019,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        34 => array(
            'name' => 'إيرادات/مصروفات تشغيلية أخرى',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 11,
            'detail_type_id' => 110,
            'status' => 'active',
            'gl_code' => 3204,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        35 => array(
            'name' => 'مصروفات إدارية وعامة أخرى',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => '136',
            'status' => 'active',
            'gl_code' => 22018,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        36 => array(
            'name' => 'أخرى (تكلفة المبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '113',
            'status' => 'active',
            'gl_code' => 2107,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        37 => array(
            'name' => 'مصروفات مكتبية',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => '136',
            'status' => 'active',
            'gl_code' => 22016,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        38 => array(
            'name' => 'وجبات وترفيه',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 136,
            'status' => 'active',
            'gl_code' => 22015,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        39 => array(
            'name' => 'مواد (تكلفة المبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '113',
            'status' => 'active',
            'gl_code' => 2106,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        40 => array(
            'name' => 'مكافآت الإدارة',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 134,
            'status' => 'active',
            'gl_code' => 22014,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        41 => array(
            'name' => 'خسائر التخلص من الأصول',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 11,
            'detail_type_id' => 107,
            'status' => 'active',
            'gl_code' => 3203,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        42 => array(
            'name' => 'خسائر عمليات متوقفة صافي الضريبة',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 133,
            'status' => 'active',
            'gl_code' => 22013,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        43 => array(
            'name' => 'استثمارات طويلة الأجل',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 4,
            'detail_type_id' => 53,
            'status' => 'active',
            'gl_code' => '1405',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        44 => array(
            'name' => 'ديون طويلة الأجل',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 8,
            'detail_type_id' => 77,
            'status' => 'active',
            'gl_code' => 4404,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        45 => array(
            'name' => 'التزامات متعلقة بأصول معروضة للبيع',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 8,
            'detail_type_id' => 76,
            'status' => 'active',
            'gl_code' => 4403,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        46 => array(
            'name' => 'أتعاب قانونية ومهنية',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 132,
            'status' => 'active',
            'gl_code' => 22012,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        47 => array(
            'name' => 'أصول المخزون',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 1,
            'detail_type_id' => 20,
            'gl_code' => '1105',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        48 => array(
            'name' => 'المخزون',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 1,
            'detail_type_id' => 20,
            'gl_code' => '1104',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        49 => array(
            'name' => 'إيرادات فوائد',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 11,
            'detail_type_id' => 106,
            'status' => 'active',
            'gl_code' => 3202,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        50 => array(
            'name' => 'مصروفات فوائد',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 131,
            'status' => 'active',
            'gl_code' => 22011,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        51 => array(
            'name' => 'أصول غير ملموسة',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 4,
            'detail_type_id' => 50,
            'status' => 'active',
            'gl_code' => '1404',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        52 => array(
            'name' => 'تأمين - مسؤولية',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 130,
            'status' => 'active',
            'gl_code' => 22010,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        53 => array(
            'name' => 'تأمين - عام',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 130,
            'status' => 'active',
            'gl_code' => 2209,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        54 => array(
            'name' => 'تأمين - إعاقة',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 130,
            'status' => 'active',
            'gl_code' => 2208,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        55 => array(
            'name' => 'ضرائب الدخل المستحقة',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 7,
            'detail_type_id' => 64,
            'status' => 'active',
            'gl_code' => 4303,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        56 => array(
            'name' => 'مصروفات ضريبة الدخل',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 129,
            'status' => 'active',
            'gl_code' => 2207,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        57 => array(
            'name' => 'الشهرة',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 4,
            'detail_type_id' => 49,
            'status' => 'active',
            'gl_code' => '1403',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        58 => array(
            'name' => 'شحن وتوصيل (تكلفة المبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '113',
            'status' => 'active',
            'gl_code' => 2105,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        59 => array(
            'name' => 'إيجار معدات',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 127,
            'status' => 'active',
            'gl_code' => 2206,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        60 => array(
            'name' => 'رسوم واشتراكات',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 126,
            'status' => 'active',
            'gl_code' => 2205,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        61 => array(
            'name' => 'أرباح مستحقة التوزيع',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 7,
            'detail_type_id' => 63,
            'status' => 'active',
            'gl_code' => 4302,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        62 => array(
            'name' => 'إيرادات توزيعات أرباح',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 11,
            'detail_type_id' => 105,
            'status' => 'active',
            'gl_code' => 3201,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        63 => array(
            'name' => 'خصومات ممنوحة (تكلفة المبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '113',
            'status' => 'active',
            'gl_code' => 2104,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        64 => array(
            'name' => 'عمالة مباشرة (تكلفة المبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '113',
            'status' => 'active',
            'gl_code' => 2103,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        65 => array(
            'name' => 'أصول ضريبية مؤجلة',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 4,
            'detail_type_id' => 48,
            'status' => 'active',
            'gl_code' => '1402',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        66 => array(
            'name' => 'تكلفة المبيعات',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '117',
            'status' => 'active',
            'gl_code' => 2102,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        67 => array(
            'name' => 'عمولات ورسوم',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 124,
            'status' => 'active',
            'gl_code' => 2204,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        68 => array(
            'name' => 'تغير في المخزون (تكلفة المبيعات)',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 12,
            'detail_type_id' => '113',
            'status' => 'active',
            'gl_code' => 2101,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        69 => array(
            'name' => 'النقد وما يعادله',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 2,
            'detail_type_id' => 30,
            'gl_code' => '1201',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        70 => array(
            'name' => 'إيرادات مصروفات قابلة للفوترة',
            'business_id' => $business_id,
            'account_primary_type' => 'income',
            'account_sub_type_id' => 10,
            'detail_type_id' => 102,
            'status' => 'active',
            'gl_code' => 3101,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        71 => array(
            'name' => 'رسوم بنكية',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 122,
            'status' => 'active',
            'gl_code' => 2203,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        72 => array(
            'name' => 'ديون معدومة',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 121,
            'status' => 'active',
            'gl_code' => 2202,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        73 => array(
            'name' => 'أصول متاحة للبيع (قصيرة الأجل)',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 1,
            'detail_type_id' => 17,
            'gl_code' => '1103',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        74 => array(
            'name' => 'أصول معروضة للبيع',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 4,
            'detail_type_id' => 47,
            'status' => 'active',
            'gl_code' => '1401',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        75 => array(
            'name' => 'مصروفات إطفاء',
            'business_id' => $business_id,
            'account_primary_type' => 'expenses',
            'account_sub_type_id' => 13,
            'detail_type_id' => 119,
            'status' => 'active',
            'gl_code' => 2201,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        76 => array(
            'name' => 'مخصص الديون المشكوك في تحصيلها',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 1,
            'detail_type_id' => 16,
            'gl_code' => '1102',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        77 => array(
            'name' => 'مجمع إهلاك الممتلكات والآلات والمعدات',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 3,
            'detail_type_id' => 37,
            'gl_code' => '1301',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        78 => array(
            'name' => 'الالتزامات غير المتداولة المستحقة',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 8,
            'detail_type_id' => 75,
            'status' => 'active',
            'gl_code' => 4402,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        79 => array(
            'name' => 'الالتزامات المستحقة',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 7,
            'detail_type_id' => 59,
            'status' => 'active',
            'gl_code' => 4301,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        80 => array(
            'name' => 'إجازات مستحقة',
            'business_id' => $business_id,
            'account_primary_type' => 'liability',
            'account_sub_type_id' => 8,
            'detail_type_id' => 74,
            'status' => 'active',
            'gl_code' => 4401,
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        81 => array(
            'name' => 'حسابات القبض (المدينون)',
            'business_id' => $business_id,
            'account_primary_type' => 'asset',
            'account_sub_type_id' => 1,
            'detail_type_id' => '15',
            'gl_code' => '1101',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
        82 => array(
            'name' => 'تكلفة البضاعة المباعة',
            'business_id' => $business_id,
            'account_primary_type' => 'cost_goods_sold',
            'account_sub_type_id' => AccountingAccountType::where('name','cost_goods_sold')->first()->id,
            'detail_type_id' => null,
            'gl_code' => '5101',
            'status' => 'active',
            'created_by' => $user_id,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ),
    );
}
    // public static function  Default_Accounts($business_id, $user_id)
    // {
    //     return   array(
    //         0 =>
    //         array(
    //             'name' => 'Accounts Payable (A/P)',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 5,
    //             'detail_type_id' => 57,
    //             'gl_code' => 4101,
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         1 =>
    //         array(
    //             'name' => 'Credit Card',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 6,
    //             'detail_type_id' => 58,
    //             'status' => 'active',
    //             'gl_code' => 4201,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         2 =>
    //         array(
    //             'name' => 'Wage expenses',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 139,
    //             'status' => 'active',
    //             'gl_code' => 220231,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         3 =>
    //         array(
    //             'name' => 'Utilities',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 148,
    //             'status' => 'active',
    //             'gl_code' => 220230,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         4 =>
    //         array(
    //             'name' => 'Unrealised loss on securities, net of tax',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 11,
    //             'detail_type_id' => 112,
    //             'status' => 'active',
    //             'gl_code' => 3205,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         5 =>
    //         array(
    //             'name' => 'Undeposited Funds',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 1,
    //             'detail_type_id' => 28,
    //             'gl_code' => '1108',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         6 =>
    //         array(
    //             'name' => 'Uncategorised Income',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 10,
    //             'detail_type_id' => 102,
    //             'status' => 'active',
    //             'gl_code' => 3108,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         7 =>
    //         array(
    //             'name' => 'Uncategorised Expense',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 137,
    //             'status' => 'active',
    //             'gl_code' => 22029,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         8 =>
    //         array(
    //             'name' => 'Uncategorised Asset',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 1,
    //             'detail_type_id' => 25,
    //             'gl_code' => '1107',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         9 =>
    //         array(
    //             'name' => 'Unapplied Cash Payment Income',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 10,
    //             'detail_type_id' => 104,
    //             'status' => 'active',
    //             'gl_code' => 3107,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         10 =>
    //         array(
    //             'name' => 'Travel expenses - selling expense',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => '146',
    //             'status' => 'active',
    //             'gl_code' => 22028,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         11 =>
    //         array(
    //             'name' => 'Travel expenses - general and admin expenses',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => '145',
    //             'status' => 'active',
    //             'gl_code' => 22027,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         12 =>
    //         array(
    //             'name' => 'Supplies',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 144,
    //             'status' => 'active',
    //             'gl_code' => 22026,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         13 =>
    //         array(
    //             'name' => 'Subcontractors - COS',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '113',
    //             'status' => 'active',
    //             'gl_code' => 2109,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         14 =>
    //         array(
    //             'name' => 'Stationery and printing',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => '136',
    //             'status' => 'active',
    //             'gl_code' => 22025,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         15 =>
    //         array(
    //             'name' => 'Short-term debit',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 7,
    //             'detail_type_id' => 68,
    //             'status' => 'active',
    //             'gl_code' => 4306,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         16 =>
    //         array(
    //             'name' => 'Shipping and delivery expense',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 142,
    //             'status' => 'active',
    //             'gl_code' => 22024,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         17 =>
    //         array(
    //             'name' => 'Sales of Product Income',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 10,
    //             'detail_type_id' => 102,
    //             'status' => 'active',
    //             'gl_code' => 3106,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         18 =>
    //         array(
    //             'name' => 'Sales - wholesale',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 10,
    //             'detail_type_id' => '101',
    //             'status' => 'active',
    //             'gl_code' => 3105,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         19 =>
    //         array(
    //             'name' => 'Sales - retail',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 10,
    //             'detail_type_id' => '100',
    //             'status' => 'active',
    //             'gl_code' => 3104,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         20 =>
    //         array(
    //             'name' => 'Sales',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 10,
    //             'detail_type_id' => 102,
    //             'status' => 'active',
    //             'gl_code' => 3103,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         21 =>
    //         array(
    //             'name' => 'Revenue - General',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 10,
    //             'detail_type_id' => '99',
    //             'status' => 'active',
    //             'gl_code' => 3102,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         22 =>
    //         array(
    //             'name' => 'Repair and maintenance',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 141,
    //             'status' => 'active',
    //             'gl_code' => 22023,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         23 =>
    //         array(
    //             'name' => 'Rent or lease payments',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 140,
    //             'status' => 'active',
    //             'gl_code' => 22022,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         24 =>
    //         array(
    //             'name' => 'Reconciliation Discrepancies',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 14,
    //             'detail_type_id' => 152,
    //             'status' => 'active',
    //             'gl_code' => 2301,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         25 =>
    //         array(
    //             'name' => 'Purchases',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 143,
    //             'status' => 'active',
    //             'gl_code' => 22021,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         26 =>
    //         array(
    //             'name' => 'Property, plant and equipment',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 3,
    //             'detail_type_id' => 41,
    //             'gl_code' => '1302',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         27 =>
    //         array(
    //             'name' => 'Prepaid Expenses',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 1,
    //             'detail_type_id' => 26,
    //             'gl_code' => '1106',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         28 =>
    //         array(
    //             'name' => 'Payroll liabilities',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 7,
    //             'detail_type_id' => 70,
    //             'status' => 'active',
    //             'gl_code' => 4305,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         29 =>
    //         array(
    //             'name' => 'Payroll Expenses',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 139,
    //             'status' => 'active',
    //             'gl_code' => 22020,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         30 =>
    //         array(
    //             'name' => 'Payroll Clearing',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 7,
    //             'detail_type_id' => 69,
    //             'status' => 'active',
    //             'gl_code' => 4304,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         31 =>
    //         array(
    //             'name' => 'Overhead - COS',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '113',
    //             'status' => 'active',
    //             'gl_code' => 2108,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         32 =>
    //         array(
    //             'name' => 'Other Types of Expenses-Advertising Expenses',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => '118',
    //             'status' => 'active',
    //             'gl_code' => 22017,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         33 =>
    //         array(
    //             'name' => 'Other selling expenses',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 138,
    //             'status' => 'active',
    //             'gl_code' => 22019,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         34 =>
    //         array(
    //             'name' => 'Other operating income (expenses)',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 11,
    //             'detail_type_id' => 110,
    //             'status' => 'active',
    //             'gl_code' => 3204,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         35 =>
    //         array(
    //             'name' => 'Other general and administrative expenses',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => '136',
    //             'status' => 'active',
    //             'gl_code' => 22018,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         36 =>
    //         array(
    //             'name' => 'Other - COS',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '113',
    //             'status' => 'active',
    //             'gl_code' => 2107,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         37 =>
    //         array(
    //             'name' => 'Office expenses',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => '136',
    //             'status' => 'active',
    //             'gl_code' => 22016,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         38 =>
    //         array(
    //             'name' => 'Meals and entertainment',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 136,
    //             'status' => 'active',
    //             'gl_code' => 22015,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         39 =>
    //         array(
    //             'name' => 'Materials - COS',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '113',
    //             'status' => 'active',
    //             'gl_code' => 2106,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         40 =>
    //         array(
    //             'name' => 'Management compensation',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 134,
    //             'status' => 'active',
    //             'gl_code' => 22014,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         41 =>
    //         array(
    //             'name' => 'Loss on disposal of assets',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 11,
    //             'detail_type_id' => 107,
    //             'status' => 'active',
    //             'gl_code' => 3203,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         42 =>
    //         array(
    //             'name' => 'Loss on discontinued operations, net of tax',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 133,
    //             'status' => 'active',
    //             'gl_code' => 22013,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         43 =>
    //         array(
    //             'name' => 'Long-term investments',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 4,
    //             'detail_type_id' => 53,
    //             'status' => 'active',
    //             'gl_code' => '1405',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         44 =>
    //         array(
    //             'name' => 'Long-term debt',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 8,
    //             'detail_type_id' => 77,
    //             'status' => 'active',
    //             'gl_code' => 4404,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         45 =>
    //         array(
    //             'name' => 'Liabilities related to assets held for sale',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 8,
    //             'detail_type_id' => 76,
    //             'status' => 'active',
    //             'gl_code' => 4403,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         46 =>
    //         array(
    //             'name' => 'Legal and professional fees',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 132,
    //             'status' => 'active',
    //             'gl_code' => 22012,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         47 =>
    //         array(
    //             'name' => 'Inventory Asset',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 1,
    //             'detail_type_id' => 20,
    //             'gl_code' => '1105',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         48 =>
    //         array(
    //             'name' => 'Inventory',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 1,
    //             'detail_type_id' => 20,
    //             'gl_code' => '1104',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         49 =>
    //         array(
    //             'name' => 'Interest income',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 11,
    //             'detail_type_id' => 106,
    //             'status' => 'active',
    //             'gl_code' => 3202,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         50 =>
    //         array(
    //             'name' => 'Interest expense',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 131,
    //             'status' => 'active',
    //             'gl_code' => 22011,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         51 =>
    //         array(
    //             'name' => 'Intangibles',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 4,
    //             'detail_type_id' => 50,
    //             'status' => 'active',
    //             'gl_code' => '1404',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         52 =>
    //         array(
    //             'name' => 'Insurance - Liability',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 130,
    //             'status' => 'active',
    //             'gl_code' => 22010,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         53 =>
    //         array(
    //             'name' => 'Insurance - General',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 130,
    //             'status' => 'active',
    //             'gl_code' => 2209,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         54 =>
    //         array(
    //             'name' => 'Insurance - Disability',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 130,
    //             'status' => 'active',
    //             'gl_code' => 2208,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         55 =>
    //         array(
    //             'name' => 'Income tax payable',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 7,
    //             'detail_type_id' => 64,
    //             'status' => 'active',
    //             'gl_code' => 4303,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         56 =>
    //         array(
    //             'name' => 'Income tax expense',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 129,
    //             'status' => 'active',
    //             'gl_code' => 2207,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         57 =>
    //         array(
    //             'name' => 'Goodwill',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 4,
    //             'detail_type_id' => 49,
    //             'status' => 'active',
    //             'gl_code' => '1403',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         58 =>
    //         array(
    //             'name' => 'Freight and delivery - COS',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '113',
    //             'status' => 'active',
    //             'gl_code' => 2105,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         59 =>
    //         array(
    //             'name' => 'Equipment rental',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 127,
    //             'status' => 'active',
    //             'gl_code' => 2206,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         60 =>
    //         array(
    //             'name' => 'Dues and Subscriptions',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 126,
    //             'status' => 'active',
    //             'gl_code' => 2205,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         61 =>
    //         array(
    //             'name' => 'Dividends payable',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 7,
    //             'detail_type_id' => 63,
    //             'status' => 'active',
    //             'gl_code' => 4302,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         62 =>
    //         array(
    //             'name' => 'Dividend income',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 11,
    //             'detail_type_id' => 105,
    //             'status' => 'active',
    //             'gl_code' => 3201,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         63 =>
    //         array(
    //             'name' => 'Discounts given - COS',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '113',
    //             'status' => 'active',
    //             'gl_code' => 2104,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         64 =>
    //         array(
    //             'name' => 'Direct labour - COS',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '113',
    //             'status' => 'active',
    //             'gl_code' => 2103,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         65 =>
    //         array(
    //             'name' => 'Deferred tax assets',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 4,
    //             'detail_type_id' => 48,
    //             'status' => 'active',
    //             'gl_code' => '1402',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         66 =>
    //         array(
    //             'name' => 'Cost of sales',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '117',
    //             'status' => 'active',
    //             'gl_code' => 2102,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         67 =>
    //         array(
    //             'name' => 'Commissions and fees',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 124,
    //             'status' => 'active',
    //             'gl_code' => 2204,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         68 =>
    //         array(
    //             'name' => 'Change in inventory - COS',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 12,
    //             'detail_type_id' => '113',
    //             'status' => 'active',
    //             'gl_code' => 2101,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         69 =>
    //         array(
    //             'name' => 'Cash and cash equivalents',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 2,
    //             'detail_type_id' => 30,
    //             'gl_code' => '1201',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         70 =>
    //         array(
    //             'name' => 'Billable Expense Income',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'income',
    //             'account_sub_type_id' => 10,
    //             'detail_type_id' => 102,
    //             'status' => 'active',
    //             'gl_code' => 3101,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         71 =>
    //         array(
    //             'name' => 'Bank charges',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 122,
    //             'status' => 'active',
    //             'gl_code' => 2203,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         72 =>
    //         array(
    //             'name' => 'Bad debts',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 121,
    //             'status' => 'active',
    //             'gl_code' => 2202,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         73 =>
    //         array(
    //             'name' => 'Available for sale assets (short-term)',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 1,
    //             'detail_type_id' => 17,
    //             'gl_code' => '1103',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         74 =>
    //         array(
    //             'name' => 'Assets held for sale',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 4,
    //             'detail_type_id' => 47,
    //             'status' => 'active',
    //             'gl_code' => '1401',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         75 =>
    //         array(
    //             'name' => 'Amortisation expense',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'expenses',
    //             'account_sub_type_id' => 13,
    //             'detail_type_id' => 119,
    //             'status' => 'active',
    //             'gl_code' => 2201,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         76 =>
    //         array(
    //             'name' => 'Allowance for bad debts',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 1,
    //             'detail_type_id' => 16,
    //             'gl_code' => '1102',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         77 =>
    //         array(
    //             'name' => 'Accumulated depreciation on property, plant and equipment',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 3,
    //             'detail_type_id' => 37,
    //             'gl_code' => '1301',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         78 =>
    //         array(
    //             'name' => 'Accrued non-current liabilities',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 8,
    //             'detail_type_id' => 75,
    //             'status' => 'active',
    //             'gl_code' => 4402,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         79 =>
    //         array(
    //             'name' => 'Accrued liabilities',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 7,
    //             'detail_type_id' => 59,
    //             'status' => 'active',
    //             'gl_code' => 4301,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         80 =>
    //         array(
    //             'name' => 'Accrued holiday payable',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'liability',
    //             'account_sub_type_id' => 8,
    //             'detail_type_id' => 74,
    //             'status' => 'active',
    //             'gl_code' => 4401,
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         81 =>
    //         array(
    //             'name' => 'Accounts Receivable (A/R)',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'asset',
    //             'account_sub_type_id' => 1,
    //             'detail_type_id' => '15',
    //             'gl_code' => '1101',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),
    //         82 =>
    //         array(
    //             'name' => 'cost_goods_sold',
    //             'business_id' => $business_id,
    //             'account_primary_type' => 'cost_goods_sold',
    //             'account_sub_type_id' => AccountingAccountType::where('name','cost_goods_sold')->first()->id,
    //             'detail_type_id' => null,
    //             'gl_code' => '5101',
    //             'status' => 'active',
    //             'created_by' => $user_id,
    //             'created_at' => Carbon::now(),
    //             'updated_at' => Carbon::now(),
    //         ),

    //     );
    // }

    public function deflute_auto_migration($request)
    {
        $user_id = request()->session()->get('user.id');
        $business_id = request()->session()->get('user.business_id');
        $names = [
            'sales_bill',
            'sell_return_bill',
            'opening_stock',
            'purchase_bill',
            'purchase_return_bill',
            'expens_bill',
            // 'sell_transfer',
            // 'purchase_transfer',
            'payroll',

        ];
        $types = [
            'sell',
            'sell_return',
            'opening_stock',
            'purchase',
            'purchase_return',
            'expense',
            // 'sell_transfer',
            // 'purchase_transfer',
            'payroll',

        ];
        $payment_status = [
            'paid',
            'due',
            'partial',
        ];

        $methods = [
            'cash',
            'card',
            'bank_transfer',
            'cheque',
        ];

       
        foreach ($types as $key => $value) {
            foreach ($payment_status as $paymentStatus) {

                if ($paymentStatus == 'due') {
                    AccountingMappingSettingAutoMigration::create([
                        'name' => $names[$key],
                        'type' => $value,
                        'location_id' => $request->input('business_location_id'),
                        'status' => 'final',
                        'payment_status' => $paymentStatus,
                        'method' => 'other',
                        'created_by' => $user_id,
                        'business_id' => $business_id,
                        'active' => false,
                    ]);
                } else {
                    foreach ($methods as $method) {
                        AccountingMappingSettingAutoMigration::create([
                            'name' => $names[$key],
                            'type' => $value,
                            'location_id' => $request->input('business_location_id'),
                            'status' => 'final',
                            'payment_status' => $paymentStatus,
                            'method' => $method,
                            'created_by' => $user_id,
                            'business_id' => $business_id,
                            'active' => false,
                        ]);
                    }
                }
            }
        }
        AccountingMappingSettingAutoMigration::create([
            'name' => 'purchase_transfer',
            'type' => 'purchase_transfer',
            'location_id' => $request->input('business_location_id'),
            'status' => 'received',
            'payment_status' => 'paid',
            'method' => 'other',
            'created_by' => $user_id,
            'business_id' => $business_id,
            'active' => false,
        ]);
        AccountingMappingSettingAutoMigration::create([
            'name' => 'sell_transfer',
            'type' => 'sell_transfer',
            'location_id' => $request->input('business_location_id'),
            'status' => 'final',
            'payment_status' => 'paid',
            'method' => 'other',
            'created_by' => $user_id,
            'business_id' => $business_id,
            'active' => false,
        ]);
       
    }
}