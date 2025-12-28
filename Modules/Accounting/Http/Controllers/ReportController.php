<?php

namespace Modules\Accounting\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\TaxRate;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Lang;
use Illuminate\Http\Request;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountType;
use Modules\Accounting\Utils\AccountingUtil;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    protected $accountingUtil;

    protected $businessUtil;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(
        AccountingUtil $accountingUtil,
        BusinessUtil $businessUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->accountingUtil = $accountingUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            ! (auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.view_reports'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $first_account = AccountingAccount::where('business_id', $business_id)
            ->where('status', 'active')
            ->first();
        $first_contact = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'converted', 'draft', 'qualified', 'supplier'])->active()->first();


        $ledger_url = null;
        $customers_suppliers_statement_url = null;
        if (! empty($first_account)) {
            $ledger_url = route('accounting.ledger', $first_account);
            $customers_suppliers_statement_url = route('accounting.customersSuppliersStatement', $first_contact);
        }


        return view('accounting::report.index')
            ->with(compact('ledger_url', 'customers_suppliers_statement_url'));
    }

    /**
     * Trial Balance
     *
     * @return Response
     */
    // public function trialBalance()
    // {
    //     $business_id = request()->session()->get('user.business_id');

    //     if (! (auth()->user()->can('superadmin') ||
    //         $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
    //         ! (auth()->user()->can('accounting.view_reports'))) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     if (! empty(request()->start_date) && ! empty(request()->end_date)) {
    //         $start_date = request()->start_date;
    //         $end_date = request()->end_date;
    //     } else {
    //         $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
    //         $start_date = $fy['start'];
    //         $end_date = $fy['end'];
    //     }

    //     $accounts = AccountingAccount::join('accounting_accounts_transactions as AAT',
    //                             'AAT.accounting_account_id', '=', 'accounting_accounts.id')
    //                         ->where('business_id', $business_id)
    //                         ->whereDate('AAT.operation_date', '>=', $start_date)
    //                         ->whereDate('AAT.operation_date', '<=', $end_date)
    //                         ->select(
    //                             DB::raw("SUM(IF(AAT.type = 'credit', AAT.amount, 0)) as credit_balance"),
    //                             DB::raw("SUM(IF(AAT.type = 'debit', AAT.amount, 0)) as debit_balance"),
    //                             'accounting_accounts.name'
    //                         )
    //                         ->groupBy('accounting_accounts.name')
    //                         ->get();

    //     return view('accounting::report.trial_balance')
    //         ->with(compact('accounts', 'start_date', 'end_date'));
    // }


    public function trialBalance(Request $request)
    {

        $account_types = AccountingAccountType::accounting_primary_type();
        $accounts_array = [];
        foreach ($account_types as $key => $account_type) {
            $accounts_array[$key] =
                $account_type['label'];
        }

        $business_id = request()->session()->get('user.business_id');

        $with_zero_balances = $request->input('with_zero_balances', 0);

        $aggregated = $request->input('aggregated', 0);

        $choose_accounts_select = $request->input('choose_accounts_select');

        $level_filter = $request->input('level_filter');

        $max_levels = AccountingAccount::where('accounting_accounts.business_id', $business_id)
            ->pluck('gl_code')->toArray();

        $lengths = array_map(function ($length) {
            return str_replace(".", "", $length);
        }, $max_levels);

        $levels = strlen(max($lengths));

        $levelsArray = [];
        for ($i = 1; $i <= $levels; $i++) {
            $levelsArray[$i] = $i;
        }

        $levelsArray = [null => __('lang_v1.all')] + $levelsArray;

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start_date = request()->input('start_date');
            $end_date =  request()->input('end_date');
        } else {
            $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
            $start_date = $fy['start'];
            $end_date = $fy['end'];
        }

        if (! $with_zero_balances) {
            $accounts = AccountingAccount::join(
                'accounting_accounts_transactions as AAT',
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
            )
                ->where(function ($query) use ($start_date, $end_date) {
                    $query->where(function ($query) use ($start_date, $end_date) {
                        $query->where('AAT.sub_type', '!=', 'opening_balance')
                            ->whereDate('AAT.operation_date', '>=', $start_date)
                            ->whereDate('AAT.operation_date', '<=', $end_date);
                    })
                        ->orWhere(function ($query) use ($start_date, $end_date) {
                            $query->where('AAT.sub_type', 'opening_balance')
                                ->whereYear('AAT.operation_date', '>=', date('Y', strtotime($start_date)))
                                ->whereYear('AAT.operation_date', '<=', date('Y', strtotime($end_date)));
                        });
                });
        } else {
            $accounts = AccountingAccount::leftJoin(
                'accounting_accounts_transactions as AAT',
                function ($join) use ($start_date, $end_date) {
                    $join->on('AAT.accounting_account_id', '=', 'accounting_accounts.id')
                        ->where(function ($query) use ($start_date, $end_date) {
                            $query->where('AAT.sub_type', '!=', 'opening_balance')
                                ->whereBetween('AAT.operation_date', [$start_date, $end_date]);
                        })
                        ->orWhere(function ($query) use ($start_date, $end_date) {
                            $query->where('AAT.sub_type', 'opening_balance')
                                ->whereYear('AAT.operation_date', '>=', date('Y', strtotime($start_date)))
                                ->whereYear('AAT.operation_date', '<=', date('Y', strtotime($end_date)));
                        });
                }
            );
        }

        $accounts->when($choose_accounts_select, function ($query, $choose_accounts_select) {
            return $query->where(function ($query) use ($choose_accounts_select) {
                foreach ($choose_accounts_select as $type) {
                    $query->orWhere('accounting_accounts.account_primary_type', 'like', $type . '%');
                }
            });
        })
            ->when($level_filter, function ($query, $level_filter) {

                return $query
                    ->whereRaw('LENGTH(REGEXP_REPLACE(accounting_accounts.gl_code, "[0-9]", "")) = ?', [$level_filter - 1])
                    ->orwhereRaw('LENGTH(REGEXP_REPLACE(accounting_accounts.gl_code, "[0-9]", "")) < ?', [$level_filter - 1]);
            })
            ->where('accounting_accounts.business_id', $business_id)

            ->select(
                DB::raw("IF($aggregated = 1, accounting_accounts.account_primary_type, accounting_accounts.name) as name"),
                DB::raw("SUM(IF(AAT.type = 'credit' AND AAT.sub_type != 'opening_balance', AAT.amount, 0)) as credit_balance"),
                DB::raw("SUM(IF(AAT.type = 'debit' AND AAT.sub_type != 'opening_balance', AAT.amount, 0)) as debit_balance"),
                DB::raw("IFNULL((SELECT AAT.amount FROM accounting_accounts_transactions as AAT 
            WHERE AAT.accounting_account_id = accounting_accounts.id 
            AND AAT.sub_type = 'opening_balance'
            AND AAT.type = 'credit'
            ORDER BY AAT.operation_date ASC 
            LIMIT 1), 0) as credit_opening_balance"),
                DB::raw("IFNULL((SELECT AAT.amount FROM accounting_accounts_transactions as AAT 
            WHERE AAT.accounting_account_id = accounting_accounts.id 
            AND AAT.sub_type = 'opening_balance'
            AND AAT.type = 'debit'
            ORDER BY AAT.operation_date ASC LIMIT 1), 0) as debit_opening_balance"),
                'AAT.sub_type as sub_type',
                'AAT.type as type',
                'accounting_accounts.gl_code',
                'accounting_accounts.id'
            )
            /* ->when($level_filter, function ($query, $level_filter) {
                return $query->havingRaw('code_length <= ?', [$level_filter - 1]);
            }) */
            ->groupBy(
                'name',
            )
            ->orderBy('accounting_accounts.gl_code');

        if ($aggregated) {
            $aggregatedAccounts = [];
            foreach ($accounts->get() as $account) {

                $groupKey = $account->name;
                if (!isset($aggregatedAccounts[$groupKey])) {
                    $aggregatedAccounts[$groupKey] =  (object) [
                        'name' => Lang::has('accounting::lang.' . $groupKey) ? __('accounting::lang.' . $groupKey) : $groupKey,
                        'gl_code' => $account->gl_code[0],
                        'credit_balance' => 0,
                        'debit_balance' => 0,
                        'credit_opening_balance' => 0,
                        'debit_opening_balance' => 0,
                    ];
                }
                $aggregatedAccounts[$groupKey]->credit_balance += $account->credit_balance;
                $aggregatedAccounts[$groupKey]->debit_balance += $account->debit_balance;
                $aggregatedAccounts[$groupKey]->credit_opening_balance += $account->credit_opening_balance;
                $aggregatedAccounts[$groupKey]->debit_opening_balance += $account->debit_opening_balance;
            }
            $accounts = $aggregatedAccounts;
        }


        if (request()->ajax()) {

            $totalDebitOpeningBalance = 0;
            $totalCreditOpeningBalance = 0;
            $totalClosingDebitBalance = 0;
            $totalClosingCreditBalance = 0;
            $totalDebitBalance = 0;
            $totalCreditBalance = 0;

            foreach ($aggregated ? $accounts : $accounts->get() as $account) {
                $totalDebitOpeningBalance += $account->debit_opening_balance;
                $totalCreditOpeningBalance += $account->credit_opening_balance;
                $totalDebitBalance += $account->debit_balance;
                $totalCreditBalance += $account->credit_balance;

                $closing_balance = $this->calculateClosingBalance($account);
                $totalClosingDebitBalance += $closing_balance['closing_debit_balance'];
                $totalClosingCreditBalance += $closing_balance['closing_credit_balance'];
            }

            return DataTables::of($accounts)
                ->editColumn('gl_code', function ($account) {
                    return $account->gl_code;
                })
                ->editColumn('name', function ($account) {
                    return $account->name;
                })
                ->editColumn('debit_opening_balance', function ($account) {
                    return $account->debit_opening_balance;
                })
                ->editColumn('credit_opening_balance', function ($account) {
                    return $account->credit_opening_balance;
                })
                ->editColumn('debit_balance', function ($account) {
                    return $account->debit_balance;
                })
                ->editColumn('credit_balance', function ($account) {
                    return $account->credit_balance;
                })
                ->addColumn('closing_debit_balance', function ($account) {
                    $closing_balance = $this->calculateClosingBalance($account);
                    return $closing_balance['closing_debit_balance'];
                })
                ->addColumn('closing_credit_balance', function ($account) {
                    $closing_balance = $this->calculateClosingBalance($account);
                    return $closing_balance['closing_credit_balance'];
                })
                ->addColumn('action', function ($account) use ($aggregated) {
                    $html = ' ';
                    if (! $aggregated) {
                        $html =
                            '<div class="btn-group">
                                <button type="button" class="btn btn-info btn-xs" >' . '
                                    <a class=" btn-modal text-white" data-container="#printledger"
                                        data-href="' . action('\Modules\Accounting\Http\Controllers\CoaController@ledgerPrint', [$account->id]) . '"
                                    >
                                        ' . __("accounting::lang.account_statement") . '
                                    </a>
                                </button>
                            </div>';
                    }
                    return $html;
                })
                ->with([
                    'totalDebitOpeningBalance' => $totalDebitOpeningBalance,
                    'totalCreditOpeningBalance' => $totalCreditOpeningBalance,
                    'totalDebitBalance' => $totalDebitBalance,
                    'totalCreditBalance' => $totalCreditBalance,
                    'totalClosingDebitBalance' => $totalClosingDebitBalance,
                    'totalClosingCreditBalance' => $totalClosingCreditBalance,
                ])
                ->make(true);
        }


        return view('accounting::report.trial_balance')
            ->with(compact('levelsArray', 'accounts_array'));
    }

    /**
     * Trial Balance
     *
     * @return Response
     */
    public function balanceSheet()
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            ! (auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.view_reports'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (! empty(request()->start_date) && ! empty(request()->end_date)) {
            $start_date = request()->start_date;
            $end_date = request()->end_date;
        } else {
            $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
            $start_date = $fy['start'];
            $end_date = $fy['end'];
        }

        $balance_formula = $this->accountingUtil->balanceFormula();

        $assets = AccountingAccount::join(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->join(
                'accounting_account_types as AATP',
                'AATP.id',
                '=',
                'accounting_accounts.account_sub_type_id'
            )
            ->whereDate('AAT.operation_date', '>=', $start_date)
            ->whereDate('AAT.operation_date', '<=', $end_date)
            ->select(DB::raw($balance_formula), 'accounting_accounts.name', 'AATP.name as sub_type')
            ->where('accounting_accounts.business_id', $business_id)
            ->whereIn('accounting_accounts.account_primary_type', ['asset'])
            ->groupBy('accounting_accounts.name')
            ->get();

        $liabilities = AccountingAccount::join(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->join(
                'accounting_account_types as AATP',
                'AATP.id',
                '=',
                'accounting_accounts.account_sub_type_id'
            )
            ->whereDate('AAT.operation_date', '>=', $start_date)
            ->whereDate('AAT.operation_date', '<=', $end_date)
            ->select(DB::raw($balance_formula), 'accounting_accounts.name', 'AATP.name as sub_type')
            ->where('accounting_accounts.business_id', $business_id)
            ->whereIn('accounting_accounts.account_primary_type', ['liability'])
            ->groupBy('accounting_accounts.name')
            ->get();

        $equities = AccountingAccount::join(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->join(
                'accounting_account_types as AATP',
                'AATP.id',
                '=',
                'accounting_accounts.account_sub_type_id'
            )
            ->whereDate('AAT.operation_date', '>=', $start_date)
            ->whereDate('AAT.operation_date', '<=', $end_date)
            ->select(DB::raw($balance_formula), 'accounting_accounts.name', 'AATP.name as sub_type')
            ->where('accounting_accounts.business_id', $business_id)
            ->whereIn('accounting_accounts.account_primary_type', ['equity'])
            ->groupBy('accounting_accounts.name')
            ->get();

        return view('accounting::report.balance_sheet')
            ->with(compact('assets', 'liabilities', 'equities', 'start_date', 'end_date'));
    }

    public function accountReceivableAgeingReport()
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            ! (auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.view_reports'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $location_id = request()->input('location_id', null);

        $report_details = $this->accountingUtil->getAgeingReport($business_id, 'sell', 'contact', $location_id);

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('accounting::report.account_receivable_ageing_report')
            ->with(compact('report_details', 'business_locations'));
    }

    public function accountPayableAgeingReport()
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            ! (auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.view_reports'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $location_id = request()->input('location_id', null);
        $report_details = $this->accountingUtil->getAgeingReport(
            $business_id,
            'purchase',
            'contact',
            $location_id
        );
        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('accounting::report.account_payable_ageing_report')
            ->with(compact('report_details', 'business_locations'));
    }

    public function accountReceivableAgeingDetails()
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            ! (auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.view_reports'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $location_id = request()->input('location_id', null);

        $report_details = $this->accountingUtil->getAgeingReport(
            $business_id,
            'sell',
            'due_date',
            $location_id
        );

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('accounting::report.account_receivable_ageing_details')
            ->with(compact('business_locations', 'report_details'));
    }

    public function accountPayableAgeingDetails()
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            ! (auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            ! (auth()->user()->can('accounting.view_reports'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $location_id = request()->input('location_id', null);

        $report_details = $this->accountingUtil->getAgeingReport(
            $business_id,
            'purchase',
            'due_date',
            $location_id
        );

        $business_locations = BusinessLocation::forDropdown($business_id, true);

        return view('accounting::report.account_payable_ageing_details')
            ->with(compact('business_locations', 'report_details'));
    }


    public function customersSuppliersStatement($contact_id, Request $request)
    {

        $business_id = request()->session()->get('user.business_id');

        $contact = Contact::where('business_id', $business_id)
            ->whereIn('type', ['customer', 'converted', 'draft', 'qualified', 'supplier'])
            ->with(['transactions'])
            ->findorFail($contact_id);


        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start_date = request()->start_date;
            $end_date =  request()->end_date;
        } else {
            $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
            $start_date = $fy['start'];
            $end_date = $fy['end'];
        }

        if ($request->ajax()) {

            $contacts = Contact::where('contacts.business_id', $business_id)

                ->where('contacts.id', $contact_id)
                ->join('transactions as t', 'contacts.id', '=', 't.contact_id')
                ->join('accounting_accounts_transactions as aat', 't.id', '=', 'aat.transaction_id')
                ->leftJoin('accounting_acc_trans_mappings as atm', 'aat.acc_trans_mapping_id', '=', 'atm.id')
                ->leftJoin('users as u', 'aat.created_by', '=', 'u.id')
                ->leftJoin('accounting_cost_centers as cc', 'aat.cost_center_id', '=', 'cc.id')
                ->select(
                    'aat.operation_date',
                    'aat.sub_type',
                    'aat.type',
                    'atm.ref_no',
                    'atm.id as atm_id',
                    'cc.ar_name as cost_center_name',
                    'atm.note',
                    'aat.amount',
                    DB::raw("CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as added_by"),
                    't.invoice_no',
                )
                ->whereDate('aat.operation_date', '>=', $start_date)
                ->whereDate('aat.operation_date', '<=', $end_date)
                ->groupBy(
                    'contacts.id',
                    'aat.operation_date',
                    'aat.sub_type',
                    'aat.type',
                    'atm_id',
                    'atm.ref_no',
                    'cc.ar_name',
                    'atm.note',
                    'aat.amount',
                    't.invoice_no',
                    'u.first_name',
                    'u.last_name',
                );


            return DataTables::of($contacts)
                ->editColumn('operation_date', function ($row) {
                    return $this->accountingUtil->format_date($row->operation_date, true);
                })
                ->editColumn('ref_no', function ($row) {
                    $description = '';
                    if ($row->sub_type == 'journal_entry') {
                        $description =  $row->ref_no;
                    }

                    if ($row->sub_type == 'sell') {
                        $description = $row->invoice_no;
                    }
                    if ($row->atm_id) {
                        $description = '<a class=" btn-modal" 
                      data-container="#printJournalEntry"
                         data-href="' . action('\Modules\Accounting\Http\Controllers\JournalEntryController@print', [$row->atm_id]) . '">
                            <i class="fa fa-print" aria-hidden="true"></i>' . $description . '
                        </a>';
                    }
                    return $description;
                })
                ->addColumn('transaction', function ($row) {
                    if (Lang::has('accounting::lang.' . $row->sub_type)) {

                        $description = __('accounting::lang.' . $row->sub_type);
                    } else {
                        $description = $row->sub_type;
                    }
                    return $description;
                })
                ->addColumn('debit', function ($row) {
                    if ($row->type == 'debit') {
                        return '<span class="debit" data-orig-value="' . $row->amount . '">' . $this->accountingUtil->num_f($row->amount, true) . '</span>';
                    }
                    return '';
                })
                ->addColumn('credit', function ($row) {
                    if ($row->type == 'credit') {
                        return '<span class="credit"  data-orig-value="' . $row->amount . '">' . $this->accountingUtil->num_f($row->amount, true) . '</span>';
                    }
                    return '';
                })
                ->filterColumn('cost_center_name', function ($query, $keyword) {
                    $query->whereRaw("LOWER(cc.ar_name) LIKE ?", ["%{$keyword}%"]);
                })
                ->filterColumn('added_by', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['ref_no', 'credit', 'debit', 'balance', 'action'])
                ->make(true);
        }

        $contact_dropdown = Contact::customersSuppliersDropdown($business_id);

        $current_bal = Contact::where('contacts.business_id', $business_id)

            ->where('contacts.id', $contact_id)
            ->join('transactions as t', 'contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
            ->leftjoin(
                'accounting_accounts as accounting_accounts',
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
            )
            ->select([DB::raw($this->accountingUtil->balanceFormula())]);

        $current_bal = $current_bal?->first()->balance;


        $total_debit_bal = Contact::join('transactions as t', 'contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
            ->leftjoin(
                'accounting_accounts as accounting_accounts',
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
            )
            ->where('contacts.business_id', $business_id)

            ->where('contacts.id', $contact_id)
            ->select(DB::raw("SUM(IF((AAT.type = 'debit'), AAT.amount, 0)) as balance"))
            ->first();
        $total_debit_bal = $total_debit_bal->balance;

        $total_credit_bal = Contact::join('transactions as t', 'contacts.id', '=', 't.contact_id')
            ->join('accounting_accounts_transactions as AAT', 't.id', '=', 'AAT.transaction_id')
            ->leftjoin(
                'accounting_accounts as accounting_accounts',
                'AAT.accounting_account_id',
                '=',
                'accounting_accounts.id'
            )
            ->where('contacts.business_id', $business_id)

            ->where('contacts.id', $contact_id)
            ->select(DB::raw("SUM(IF((AAT.type = 'credit'), AAT.amount, 0)) as balance"))
            ->first();

        $total_credit_bal = $total_credit_bal->balance;



        return view('accounting::chart_of_accounts.customers-suppliers-statement')
            ->with(compact('contact', 'contact_dropdown', 'current_bal', 'total_debit_bal', 'total_credit_bal'));
    }


    public function incomeStatement()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!empty(request()->start_date) && !empty(request()->end_date)) {
            $start_date = request()->start_date;
            $end_date =  request()->end_date;
        } else {
            $fy = $this->businessUtil->getCurrentFinancialYear($business_id);
            $start_date = $fy['start'];
            $end_date = $fy['end'];
        }
        $accounts = AccountingAccount::join(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->where('business_id', $business_id)
            ->where(function ($qu) {
                $qu->where('accounting_accounts.account_primary_type', '!=', 'asset')
                    ->where('accounting_accounts.account_primary_type', '!=', 'liability');
            })
            ->where(function ($query) use ($start_date, $end_date) {
                $query->where(function ($query) use ($start_date, $end_date) {
                    $query->where('AAT.sub_type', '!=', 'opening_balance')
                        ->whereDate('AAT.operation_date', '>=', $start_date)
                        ->whereDate('AAT.operation_date', '<=', $end_date);
                })
                    ->orWhere(function ($query) use ($start_date, $end_date) {
                        $query->where('AAT.sub_type', 'opening_balance')
                            ->whereYear('AAT.operation_date', '>=', date('Y', strtotime($start_date)))
                            ->whereYear('AAT.operation_date', '<=', date('Y', strtotime($end_date)));
                    });
            })
            ->select(
                DB::raw("SUM(IF(AAT.type = 'credit' AND AAT.sub_type != 'opening_balance', AAT.amount, 0)) as credit_balance"),
                DB::raw("SUM(IF(AAT.type = 'debit' AND AAT.sub_type != 'opening_balance', AAT.amount, 0)) as debit_balance"),
                DB::raw("IFNULL(
                    (SELECT AAT2.amount 
                     FROM accounting_accounts_transactions as AAT2 
                     WHERE AAT2.accounting_account_id = accounting_accounts.id 
                     AND AAT2.sub_type = 'opening_balance'
                     AND AAT2.type = 'credit'
                     ORDER BY AAT2.operation_date ASC 
                     LIMIT 1), 
                    0) as credit_opening_balance"),
                DB::raw("IFNULL(
                    (SELECT AAT2.amount 
                     FROM accounting_accounts_transactions as AAT2 
                     WHERE AAT2.accounting_account_id = accounting_accounts.id 
                     AND AAT2.sub_type = 'opening_balance'
                     AND AAT2.type = 'debit'
                     ORDER BY AAT2.operation_date ASC 
                     LIMIT 1), 
                    0) as debit_opening_balance"),
                'accounting_accounts.name',
                'accounting_accounts.gl_code',
                'accounting_accounts.account_primary_type as acc_type'
            )

            ->groupBy('accounting_accounts.name')
            ->orderBy('accounting_accounts.gl_code')
            ->get();

        $data = $this->getIcomeStatementData($accounts);

        return view('accounting::report.income-statement')
            ->with(compact(
                'accounts',
                'start_date',
                'end_date',
                'data'
            ));
    }

    public function getIcomeStatementData($accounts)
    {
        $total_debit = 0;
        $total_credit = 0;
        $cost_of_revenue = 0;
        $total_expense = 0;
        $revenue_net = 0;
        $total_other_income = 0;
        $total_other_expense = 0;
        $total_balances = [];

        foreach ($accounts as $account) {
            if (
                str_starts_with($account->gl_code, '31') ||    // Revenues
                str_starts_with($account->gl_code, '32') ||    // Other Revenues
                str_starts_with($account->gl_code, '51') ||
                str_starts_with($account->gl_code, '21') ||
                str_starts_with($account->gl_code, '22') ||
                str_starts_with($account->gl_code, '23')
            ) {
                $total_debit += $account->debit_balance;
                $total_credit += $account->credit_balance;

                $debit_balance = $account->debit_opening_balance + $account->debit_balance;
                $credit_balance = $account->credit_opening_balance + $account->credit_balance;

                $balance = $credit_balance - $debit_balance;

                $total_balances[$account->gl_code] = $balance;
            }
        }

        foreach ($total_balances as $key => $total_balance) {
            if (str_starts_with($key, '3') || str_starts_with($key, '32')) { // الإيرادات
                $revenue_net += $total_balance;
            } elseif (str_starts_with($key, '51')) { // المصروفات التشغيلية
                $cost_of_revenue += abs($total_balance);
            } elseif (str_starts_with($key, '22') || str_starts_with($key, '21') || str_starts_with($key, '23')) { // المصروفات الأخرى
                $total_expense += abs($total_balance);
            }
        }

        $gross_profit = $revenue_net - $cost_of_revenue;
        $operation_income = $gross_profit - $total_expense;
        $income_before_tax = $operation_income + $total_other_income - $total_other_expense;

        $tax = TaxRate::first()->amount;
        $tax_amount = ($tax * $income_before_tax) / 100;

        return (object)[
            'gross_profit' => $gross_profit,
            'operation_income' => $operation_income,
            'income_before_tax' => $income_before_tax,
            'tax_amount' => $tax_amount,
            'revenue_net' => $revenue_net,
            'cost_of_revenue' => $cost_of_revenue,
            'total_expense' => $total_expense,
            'total_other_income' => $total_other_income,
            'total_other_expense' => $total_other_expense
        ];
    }

    private function calculateClosingBalance($account)
    {
        $closing_debit_balance = $account->debit_opening_balance + $account->debit_balance;
        $closing_credit_balance = $account->credit_opening_balance + $account->credit_balance;
        $closing_balance = $closing_credit_balance - $closing_debit_balance;

        return [
            'closing_debit_balance' => $closing_balance < 0 ? abs($closing_balance) : 0,
            'closing_credit_balance' => $closing_balance >= 0 ? $closing_balance : 0,
        ];
    }
}