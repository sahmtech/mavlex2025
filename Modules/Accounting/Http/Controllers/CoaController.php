<?php

namespace Modules\Accounting\Http\Controllers;

use App\Utils\ModuleUtil;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB as FacadesDB;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccountType;
use Modules\Accounting\Utils\AccountingUtil;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CoaController extends Controller
{
    protected $accountingUtil;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(AccountingUtil $accountingUtil, ModuleUtil $moduleUtil)
    {
        $this->accountingUtil = $accountingUtil;
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

        if (!(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') || auth()->user()->can('accounting.manage_accounts'))) {
            abort(403, 'Unauthorized action.');
        }

        $cost_goods_sold_check =  AccountingAccountType::where('name', 'cost_goods_sold')->first();
        if (!$cost_goods_sold_check) {
            $cost_goods_sold= AccountingAccountType::create([
                'name' => 'cost_goods_sold',
                'created_by' => Auth::user()->id,
                'account_primary_type' => 'cost_goods_sold',
                'account_type' => 'sub_type',
            ]);
            AccountingAccount::create([
                'name' => 'cost_goods_sold',
                'business_id' => $business_id,
                'account_primary_type' => 'cost_goods_sold',
                'account_sub_type_id' => $cost_goods_sold->id,
                'detail_type_id' => null,
                'gl_code' => '5101',
                'status' => 'active',
                'created_by' =>Auth::user()->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }




        $account_types = AccountingAccountType::accounting_primary_type();

        $account_GLC = [];
        foreach ($account_types as $k => $v) {
            $account_types[$k] = $v['label'];
            $account_GLC[$k] = $v['GLC'];
        }

        // foreach ($account_types as $k => $v) {
        //     $account_types[$k] = $v['label'];
        // }

        if (request()->ajax()) {
            $balance_formula = $this->accountingUtil->balanceFormula('AA');

            $query = AccountingAccount::where('business_id', $business_id)
                ->whereNull('parent_account_id')
                ->with([
                    'child_accounts' => function ($query) use ($balance_formula) {
                        $query->select([DB::raw("(SELECT $balance_formula from accounting_accounts_transactions AS AAT
                                        JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                        WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"), 'accounting_accounts.*']);
                    },
                    'child_accounts.detail_type',
                    'detail_type',
                    'account_sub_type',
                    'child_accounts.account_sub_type',
                ])
                ->select([
                    DB::raw("(SELECT $balance_formula
                                    FROM accounting_accounts_transactions AS AAT 
                                    JOIN accounting_accounts AS AA ON AAT.accounting_account_id = AA.id
                                    WHERE AAT.accounting_account_id = accounting_accounts.id) AS balance"),
                    'accounting_accounts.*',
                ]);

            if (!empty(request()->input('account_type'))) {
                $query->where('accounting_accounts.account_primary_type', request()->input('account_type'));
            }
            if (!empty(request()->input('status'))) {
                $query->where('accounting_accounts.status', request()->input('status'));
            }

            $accounts = $query->get();

            $account_exist = AccountingAccount::where('business_id', $business_id)->exists();

            if (request()->input('view_type') == 'table') {
                return view('accounting::chart_of_accounts.accounts_table')
                    ->with(compact('accounts', 'account_exist'));
            } else {
                $account_sub_types = AccountingAccountType::where('account_type', 'sub_type')
                    ->where(function ($q) use ($business_id) {
                        $q->whereNull('business_id')
                            ->orWhere('business_id', $business_id);
                    })
                    ->get();

                return view('accounting::chart_of_accounts.accounts_tree')
                    ->with(compact('accounts', 'account_exist', 'account_GLC', 'account_types', 'account_sub_types'));
            }
        }

        return view('accounting::chart_of_accounts.index')->with(compact('account_types'));
    }

    public function open_create_dialog($id)
    {
        $parent_accounts = $id;
        return view('accounting::chart_of_accounts.create', compact('parent_accounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        if (
            !(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            (auth()->user()->can('accounting.manage_accounts'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $account_types = AccountingAccountType::accounting_primary_type();

            return view('accounting::chart_of_accounts.create')->with(compact('account_types'));
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function createDefaultAccounts()
    {
        //check no accounts
        $business_id = request()->session()->get('user.business_id');
        $cost_goods_sold_check =  AccountingAccountType::where('name', 'cost_goods_sold')->first();
        if (!$cost_goods_sold_check) {
            AccountingAccountType::create([
                'name' => 'cost_goods_sold',
                'created_by' => Auth::user()->id,
                'account_primary_type' => 'cost_goods_sold',
                'account_type' => 'sub_type',
            ]);
        }
        if (
            !(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') ||
                auth()->user()->can('accounting.manage_accounts'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $user_id = request()->session()->get('user.id');

        $default_accounts = AccountingUtil::Default_Accounts($business_id, $user_id);


        if (AccountingAccount::where('business_id', $business_id)->doesntExist()) {
            AccountingAccount::insert($default_accounts);
        }

        //redirect back
        $output = [
            'success' => 1,
            'msg' => __('lang_v1.added_success'),
        ];

        return redirect()->back()->with('status', $output);
    }

    public function getAccountDetailsType()
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            !(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') ||
                auth()->user()->can('accounting.manage_accounts'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $account_type_id = request()->input('account_type_id');
            $detail_types_obj = AccountingAccountType::where('parent_id', $account_type_id)
                ->where(function ($q) use ($business_id) {
                    $q->whereNull('business_id')
                        ->orWhere('business_id', $business_id);
                })
                ->where('account_type', 'detail_type')
                ->get();

            $parent_accounts = AccountingAccount::where('business_id', $business_id)
                ->where('account_sub_type_id', $account_type_id)
                ->whereNull('parent_account_id')
                ->select('name as text', 'id')
                ->get();
            $parent_accounts->prepend([
                'id' => 'null',
                'text' => __('messages.please_select'),
            ]);

            $detail_types = [[
                'id' => 'null',
                'text' => __('messages.please_select'),
                'description' => '',
            ]];

            foreach ($detail_types_obj as $detail_type) {
                $detail_types[] = [
                    'id' => $detail_type->id,
                    'text' => __('accounting::lang.' . $detail_type->name),
                    'description' => !empty($detail_type->description) ?
                        __('accounting::lang.' . $detail_type->description) : '',
                ];
            }

            return [
                'detail_types' => $detail_types,
                'parent_accounts' => $parent_accounts,
            ];
        }
    }

    public function getAccountSubTypes()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $account_primary_type = request()->input('account_primary_type');
            $sub_types_obj = AccountingAccountType::where('account_primary_type', $account_primary_type)
                ->where(function ($q) use ($business_id) {
                    $q->whereNull('business_id')
                        ->orWhere('business_id', $business_id);
                })
                ->where('account_type', 'sub_type')
                ->get();

            $sub_types = [[
                'id' => 'null',
                'text' => __('messages.please_select'),
                'show_balance' => 0,
            ]];

            foreach ($sub_types_obj as $st) {
                $sub_types[] = [
                    'id' => $st->id,
                    'text' => $st->account_type_name,
                    'show_balance' => $st->show_balance,
                ];
            }

            return [
                'sub_types' => $sub_types,
            ];
        }
    }



    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        try {
            DB::beginTransaction();

            $input = $request->only([
                'name',
                'account_category',
                'parent_account_id'
            ]);
            $account_account = AccountingAccount::find($input['parent_account_id']);
            $input['account_primary_type'] = $account_account->account_primary_type;
            $input['account_sub_type_id'] = $account_account->account_sub_type_id;
            $input['detail_type_id'] = $account_account->detail_type_id;
            $input['created_by'] = auth()->user()->id;
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['status'] = 'active';
            $input['gl_code'] = AccountingUtil::next_GLC($input['parent_account_id'], $business_id);
            $account_type = AccountingAccountType::find($input['account_sub_type_id']);
            $account = AccountingAccount::create($input);
            // return $input;
            if ($account_type->show_balance == 1 && !empty($request->input('balance'))) {
                //Opening balance
                $data = [
                    'amount' => $this->accountingUtil->num_uf($request->input('balance')),
                    'accounting_account_id' => $account->id,
                    'created_by' => auth()->user()->id,
                    'operation_date' => !empty($request->input('balance_as_of')) ?
                        $this->accountingUtil->uf_date($request->input('balance_as_of')) :
                        \Carbon::today()->format('Y-m-d')
                ];

                //Opening balance
                $data['type'] = in_array($input['account_primary_type'], ['asset', 'expenses']) ? 'debit' : 'credit';
                $data['sub_type'] = 'opening_balance';
                $trans = AccountingAccountsTransaction::query()->create($data);
                $opBalance = [
                    'acc_transaction_id' => $trans->id,
                    'type' => $data['type'] == 'debit' ? 'debit' : 'credit',
                    'business_id' => $business_id,
                    'year' => Carbon::today()->format('Y')
                ];
                OpeningBalance::query()->create($opBalance);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return redirect()->back()->with([
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ]);
        }

        return redirect()->back();
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id) {}

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');



        if (request()->ajax()) {
            $account = AccountingAccount::where('business_id', $business_id)
                ->with(['detail_type'])
                ->find($id);

            $account_types = AccountingAccountType::accounting_primary_type();
            $account_sub_types = AccountingAccountType::where('account_primary_type', $account->account_primary_type)
                ->where('account_type', 'sub_type')
                ->where(function ($q) use ($business_id) {
                    $q->whereNull('business_id')
                        ->orWhere('business_id', $business_id);
                })
                ->get();
            $account_detail_types = AccountingAccountType::where('parent_id', $account->account_sub_type_id)
                ->where('account_type', 'detail_type')
                ->where(function ($q) use ($business_id) {
                    $q->whereNull('business_id')
                        ->orWhere('business_id', $business_id);
                })
                ->get();

            $parent_accounts = AccountingAccount::where('business_id', $business_id)
                ->where('account_sub_type_id', $account->account_sub_type_id)
                ->whereNull('parent_account_id')
                ->get();

            return view('accounting::chart_of_accounts.edit')->with(compact(
                'account_types',
                'account',
                'account_sub_types',
                'account_detail_types',
                'parent_accounts'
            ));
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');


        try {
            DB::beginTransaction();

            $input = $request->only([
                'name',
                'account_category'
            ]);

            // $input['parent_account_id'] = !empty($input['parent_account_id'])
            //     && $input['parent_account_id'] !== 'null' ? $input['parent_account_id'] : null;

            $account = AccountingAccount::find($id);
            $account->update($input);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());
            return redirect()->back()->with([
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ]);
        }

        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

    public function activateDeactivate($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (
            (auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            !(auth()->user()->can('accounting.manage_accounts'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $account = AccountingAccount::where('business_id', $business_id)
                ->find($id);

            $account->status = $account->status == 'active' ? 'inactive' : 'active';
            $account->save();

            $msg = $account->status == 'active' ? __('accounting::lang.activated_successfully') :
                __('accounting::lang.deactivated_successfully');
            $output = [
                'success' => 1,
                'msg' => $msg,
            ];

            return $output;
        }
    }

    /**
     * Displays the ledger of the account
     *
     * @param  int  $account_id
     * @return Response
     */
    public function ledger($account_id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            !(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') ||
                auth()->user()->can('accounting.manage_accounts'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $account = AccountingAccount::where('business_id', $business_id)
            ->with(['account_sub_type', 'detail_type'])
            ->findorFail($account_id);

        if (request()->ajax()) {
            $start_date = request()->input('start_date');
            $end_date = request()->input('end_date');

            // $before_bal_query = AccountingAccountsTransaction::where('accounting_account_id', $account->id)
            //                     ->leftjoin('accounting_acc_trans_mappings as ATM', 'accounting_accounts_transactions.acc_trans_mapping_id', '=', 'ATM.id')
            //         ->select([
            //             DB::raw('SUM(IF(accounting_accounts_transactions.type="credit", accounting_accounts_transactions.amount, -1 * accounting_accounts_transactions.amount)) as prev_bal')])
            //         ->where('accounting_accounts_transactions.operation_date', '<', $start_date);
            // $bal_before_start_date = $before_bal_query->first()->prev_bal;

            $transactions = AccountingAccountsTransaction::where('accounting_account_id', $account->id)
                ->leftjoin('accounting_acc_trans_mappings as ATM', 'accounting_accounts_transactions.acc_trans_mapping_id', '=', 'ATM.id')
                ->leftjoin('transactions as T', 'accounting_accounts_transactions.transaction_id', '=', 'T.id')
                ->leftjoin('users AS U', 'accounting_accounts_transactions.created_by', 'U.id')
                ->select(
                    'accounting_accounts_transactions.operation_date',
                    'accounting_accounts_transactions.sub_type',
                    'accounting_accounts_transactions.type',
                    'ATM.ref_no as a_ref',
                    'ATM.note',
                    'accounting_accounts_transactions.amount',
                    DB::raw("CONCAT(COALESCE(U.surname, ''),' ',COALESCE(U.first_name, ''),' ',COALESCE(U.last_name,'')) as added_by"),
                    'T.invoice_no',
                    'T.ref_no'
                );
            if (!empty($start_date) && !empty($end_date)) {
                $transactions->whereDate('accounting_accounts_transactions.operation_date', '>=', $start_date)
                    ->whereDate('accounting_accounts_transactions.operation_date', '<=', $end_date);
            }

            return DataTables::of($transactions)
                ->editColumn('operation_date', function ($row) {
                    return $this->accountingUtil->format_date($row->operation_date, true);
                })
                ->editColumn('ref_no', function ($row) {
                    $description = '';

                    if ($row->sub_type == 'journal_entry') {
                        $description = '<b>' . __('accounting::lang.journal_entry') . '</b>';
                        $description .= '<br>' . __('purchase.ref_no') . ': ' . $row->a_ref;
                    }

                    if ($row->sub_type == 'opening_balance') {
                        $description = '<b>' . __('accounting::lang.opening_balance') . '</b>';
                    }

                    if ($row->sub_type == 'sell') {
                        $description = '<b>' . __('sale.sale') . '</b>';
                        $description .= '<br>' . __('sale.invoice_no') . ': ' . $row->invoice_no;
                    }

                    if ($row->sub_type == 'expense') {
                        $description = '<b>' . __('accounting::lang.expense') . '</b>';
                        $description .= '<br>' . __('purchase.ref_no') . ': ' . $row->ref_no;
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
                // ->addColumn('balance', function ($row) use ($bal_before_start_date, $start_date) {
                //     //TODO:: Need to fix same balance showing for transactions having same operation date
                //     $current_bal = AccountingAccountsTransaction::where('accounting_account_id',
                //                         $row->account_id)
                //                     ->where('operation_date', '>=', $start_date)
                //                     ->where('operation_date', '<=', $row->operation_date)
                //                     ->select(DB::raw("SUM(IF(type='credit', amount, -1 * amount)) as balance"))
                //                     ->first()->balance;
                //     $bal = $bal_before_start_date + $current_bal;
                //     return '<span class="balance" data-orig-value="' . $bal . '">' . $this->accountingUtil->num_f($bal, true) . '</span>';
                // })
                ->editColumn('action', function ($row) {
                    $action = '';

                    return $action;
                })
                ->filterColumn('added_by', function ($query, $keyword) {
                    $query->whereRaw("CONCAT(COALESCE(u.surname, ''), ' ', COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) like ?", ["%{$keyword}%"]);
                })
                ->rawColumns(['ref_no', 'credit', 'debit', 'balance', 'action'])
                ->make(true);
        }

        $current_bal = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->where('business_id', $business_id)
            ->where('accounting_accounts.id', $account->id)
            ->select([DB::raw($this->accountingUtil->balanceFormula())]);
        $current_bal = $current_bal->first()->balance;

        return view('accounting::chart_of_accounts.ledger')
            ->with(compact('account', 'current_bal'));
    }

    public function ledgerPrint($account_id)
    {
        $business_id = request()->session()->get('user.business_id');

        $account = AccountingAccount::where('business_id', $business_id)
            ->with(['account_sub_type', 'detail_type'])
            ->findorFail($account_id);

        $transactions = AccountingAccountsTransaction::where('accounting_account_id', $account->id)
            ->leftjoin('accounting_acc_trans_mappings as ATM', 'accounting_accounts_transactions.acc_trans_mapping_id', '=', 'ATM.id')
            ->leftjoin('transactions as T', 'accounting_accounts_transactions.transaction_id', '=', 'T.id')
            ->leftjoin('users AS u', 'accounting_accounts_transactions.created_by', 'u.id')

            ->leftjoin('accounting_cost_centers AS cc', 'accounting_accounts_transactions.cost_center_id', 'cc.id')
            ->select(
                'accounting_accounts_transactions.operation_date',
                'accounting_accounts_transactions.sub_type',
                'accounting_accounts_transactions.type',
                'ATM.ref_no as ref_no',
                'ATM.id as id',
                'cc.ar_name as cost_center_name',
                'ATM.note',
                'accounting_accounts_transactions.amount',
                DB::raw("CONCAT(COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                'T.invoice_no',

            )->get();


        $current_bal = AccountingAccount::leftjoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->where('business_id', $business_id)
            ->where('accounting_accounts.id', $account->id)
            ->select([DB::raw($this->accountingUtil->balanceFormula())]);
        $current_bal = $current_bal->first()->balance;

        $total_debit_bal = AccountingAccount::leftJoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->where('business_id', $business_id)

            ->where('accounting_accounts.id', $account->id)
            ->select(DB::raw("SUM(IF((AAT.type = 'debit'), AAT.amount, 0)) as balance"))
            ->first();
        $total_debit_bal = $total_debit_bal->balance;

        $total_credit_bal = AccountingAccount::leftJoin(
            'accounting_accounts_transactions as AAT',
            'AAT.accounting_account_id',
            '=',
            'accounting_accounts.id'
        )
            ->where('business_id', $business_id)

            ->where('accounting_accounts.id', $account->id)
            ->select(DB::raw("SUM(IF((AAT.type = 'credit'), AAT.amount, 0)) as balance"))
            ->first();

        $total_credit_bal = $total_credit_bal->balance;

        return view('accounting::report.ledger-print', compact('account', 'transactions', 'current_bal', 'total_debit_bal', 'total_credit_bal'));
    }
    public function viewImporte_accounts()
    {
        $is_admin = auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ? true : false;
        $can_import_accountss = auth()->user()->can('accouning.import_accounts');

        if (!($is_admin || $can_import_accountss)) {
            return redirect()->route('home')->with('status', [
                'success' => false,
                'msg' => __('message.unauthorized'),
            ]);
        }
        return view('accounting::chart_of_accounts.import_accounts');
    }


    public function importe_accounts(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $is_admin = auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ? true : false;
        $can_import_accountss = auth()->user()->can('accouning.import_accounts');

        if (!($is_admin || $can_import_accountss)) {
            return redirect()->route('home')->with('status', [
                'success' => false,
                'msg' => __('message.unauthorized'),
            ]);
        }

        try {

            if ($request->hasFile('accounts_csv')) {
                $file = $request->file('accounts_csv');
                $parsed_array = Excel::toArray([], $file);
                $accounts_csv = array_splice($parsed_array[0], 1);
                FacadesDB::beginTransaction();
                $business_id = request()->session()->get('user.business_id');

                $user_id = request()->session()->get('user.id');

                foreach ($accounts_csv as  $value) {


                    if (!$value[0] || !$value[1] || !$value[2]  || !$value[3] || !$value[4]) {
                        continue;
                    } else {
                        $accountingAccountType = AccountingAccountType::where('name', $value[4])->first();
                        if (!$accountingAccountType || AccountingAccount::where('gl_code', $value[3])->first()) {
                            continue;
                        } else {

                            AccountingAccount::create([
                                'name' => $value[0],
                                'business_id' => $business_id,

                                'account_primary_type' => $value[2],
                                'account_sub_type_id' => $accountingAccountType->id,
                                'detail_type_id' =>  $accountingAccountType->account_type == 'sub_type' ? null : AccountingAccountType::find($accountingAccountType->parent_id)->id,
                                'gl_code' => $value[3],
                                'status' => 'active',
                                'created_by' => $user_id,
                                'created_at' => Carbon::now(),
                                'updated_at' => Carbon::now(),
                            ]);
                        }
                    }
                }

                DB::commit();

                return redirect()->back()
                    ->with('status', [
                        'success' => 1,
                        'msg' => __('lang_v1.added_success')
                    ]);
            }
            return redirect()->back()
                ->with('status', [
                    'success' => 1,
                    'msg' => __('lang_v1.added_success')
                ]);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ]);
        }
    }
}