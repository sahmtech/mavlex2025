<?php

namespace Modules\Accounting\Http\Controllers;

use App\Contact;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccountsTransactionHistory;
use Modules\Accounting\Entities\AccountingAccTransMapping;
use Modules\Accounting\Entities\AccountingAccTransMappingHistory;
use Modules\Accounting\Entities\CostCenter;
use Modules\Accounting\Services\AccountingPeriodLockService;
use Modules\Accounting\Utils\AccountingUtil;
use Modules\Accounting\Utils\JournalEntryValidator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Auth;

class JournalEntryController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $util;

    protected AccountingPeriodLockService $periodLockService;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(Util $util, ModuleUtil $moduleUtil, AccountingUtil $accountingUtil, AccountingPeriodLockService $periodLockService)
    {
        $this->util = $util;
        $this->moduleUtil = $moduleUtil;
        $this->accountingUtil = $accountingUtil;
        $this->periodLockService = $periodLockService;
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
            !(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') ||
                auth()->user()->can('accounting.journals'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $is_superadmin = auth()->user()->can('superadmin');
        $is_admin = auth()->user()->can('Admin#' . request()->session()->get('user.business_id'));
        $can_delete_journals = auth()->user()->can('accounting.delete_journal');
        $can_edit_journals = auth()->user()->can('accounting.edit_journal');
        if (request()->ajax()) {
            $journal = AccountingAccTransMapping::where('accounting_acc_trans_mappings.business_id', $business_id)
                ->join('users as u', 'accounting_acc_trans_mappings.created_by', 'u.id')
                ->where('type', 'journal_entry')
                ->select([
                    'accounting_acc_trans_mappings.id',
                    'ref_no',
                    'operation_date',
                    'note',
                    'path_file',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                ]);

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $journal->whereDate('accounting_acc_trans_mappings.operation_date', '>=', $start)
                    ->whereDate('accounting_acc_trans_mappings.operation_date', '<=', $end);
            }

            return Datatables::of($journal)
                ->addColumn(
                    'action',
                    function ($row) use ($can_delete_journals, $can_edit_journals, $is_admin, $is_superadmin) {
                        $html = '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                    data-toggle="dropdown" aria-expanded="false">' .
                            __('messages.actions') .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-left" role="menu">';

                        if ($is_admin || $can_edit_journals || $is_superadmin) {
                            $html .= '<li>
                                    <a href="' . action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'edit'], [$row->id]) . '">
                                        <i class="fas fa-edit"></i>' . __('messages.edit') . '
                                    </a>
                                </li>';
                        }
                      
                            $html .= '<li>
                                    <a href="' . action('\Modules\Accounting\Http\Controllers\JournalEntryController@history_index', [$row->id]) . '">
                                        <i class="fas fa-history" aria-hidden="true"></i>' . __("accounting::lang.history_edit") . '
                                    </a>
                                </li><li>
                                    <a class=" btn-modal" 
                                  data-container="#printJournalEntry"
                                     data-href="' . action('\Modules\Accounting\Http\Controllers\JournalEntryController@print', [$row->id]) . '">
                                        <i class="fa fa-print" aria-hidden="true"></i>' . __("messages.print") . '
                                    </a>
                                </li>';
                     
                        
                        if ($is_admin || $can_delete_journals || $is_superadmin) {
                            $html .= '<li>
                                    <a href="#" data-href="' . action([\Modules\Accounting\Http\Controllers\JournalEntryController::class, 'destroy'], [$row->id]) . '" class="delete_journal_button">
                                        <i class="fas fa-trash" aria-hidden="true"></i>' . __('messages.delete') . '
                                    </a>
                                    </li>';
                        }

                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->addColumn('path_file', function ($row) {
                    $html = '';
                    if (!empty($row->path_file)) {
                        $html .= '<button class="btn btn-xs btn-info "  onclick="window.location.href = \'/uploads/' . $row->path_file . '\'"><i class="fa fa-eye"></i> ' . __('accounting::lang.attachment_view') . '</button>';
                        '&nbsp;';
                    } else {
                        $html .= '<span class="text-warning">' . __('accounting::lang.no_attachment_to_show') . '</span>';
                    }
                    return $html;
                })
                ->rawColumns(['action', 'path_file'])
                ->make(true);
        }

        return view('accounting::journal_entry.index');
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
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') ||
                auth()->user()->can('accounting.add_journal'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $allCenters = CostCenter::query()->get();

        return view('accounting::journal_entry.create', compact('allCenters'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');



        try {
            $user_id = request()->session()->get('user.id');

            $account_ids = $request->get('account_id');
            $credits = $request->get('credit');
            $debits = $request->get('debit');
            $journal_date = $request->get('journal_date');
            $additional_notes = $request->get('additional_notes');
            $cost_centers = $request->get('cost_center');

            $journalDateParsed = $this->util->uf_date($journal_date, true);
            $this->periodLockService->assertUnlocked($business_id, $journalDateParsed);

            $parseAmount = fn ($v) => $this->util->num_uf($v ?? 0);
            $validation = JournalEntryValidator::validateJournalLines($account_ids ?? [], $debits ?? [], $credits ?? [], $parseAmount);
            if (! $validation['ok']) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => __('accounting::lang.journal_validation_'.$validation['error']),
                ]);
            }

            if (! JournalEntryValidator::accountsBelongToBusiness($account_ids ?? [], $business_id)) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => __('accounting::lang.journal_invalid_accounts'),
                ]);
            }

            DB::beginTransaction();

            $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);

            $ref_no = $request->get('ref_no');
            $ref_count = $this->util->setAndGetReferenceCount('journal_entry');
            if (empty($ref_no)) {
                $prefix = !empty($accounting_settings['journal_entry_prefix']) ?
                    $accounting_settings['journal_entry_prefix'] : '';

                //Generate reference number
                $ref_no = $this->util->generateReferenceNumber('journal_entry', $ref_count, $business_id, $prefix);
            }

            $acc_trans_mapping = new AccountingAccTransMapping();
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('/journal_entry');
                $acc_trans_mapping->path_file = $attachment_name;
            }
            $acc_trans_mapping->business_id = $business_id;
            $acc_trans_mapping->ref_no = $ref_no;
            $acc_trans_mapping->note = $request->get('note');
            $acc_trans_mapping->type = 'journal_entry';
            $acc_trans_mapping->created_by = $user_id;
            $acc_trans_mapping->operation_date = $journalDateParsed;
            $acc_trans_mapping->save();

            //save details in account trnsactions table
            foreach ($account_ids as $index => $account_id) {
                if (!empty($account_id)) {
                    $transaction_row = [];
                    $transaction_row['accounting_account_id'] = $account_id;

                    if (!empty($credits[$index])) {
                        $transaction_row['amount'] = $this->util->num_uf($credits[$index]);
                        $transaction_row['type'] = 'credit';
                    }

                    if (!empty($debits[$index])) {
                        $transaction_row['amount'] = $this->util->num_uf($debits[$index]);
                        $transaction_row['type'] = 'debit';
                    }
                    $transaction_row['cost_center_id'] = $cost_centers[$index] ?? null;
                    $transaction_row['additional_notes'] = $additional_notes[$index];
                    $transaction_row['created_by'] = $user_id;
                    $transaction_row['operation_date'] = $journalDateParsed;
                    $transaction_row['sub_type'] = 'journal_entry';
                    $transaction_row['acc_trans_mapping_id'] = $acc_trans_mapping->id;

                    $accounts_transactions = new AccountingAccountsTransaction();
                    $accounts_transactions->fill($transaction_row);
                    $accounts_transactions->save();
                }
            }

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.added_success'),
            ];
        } catch (\RuntimeException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->route('journal-entry.index')->with('status', $output);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (
            !(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')) ||
            auth()->user()->can('accounting.view_journal')
        ) {
            abort(403, 'Unauthorized action.');
        }

        return view('accounting::journal_entry.show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') || auth()->user()->can('accounting.edit_journal'))) {
            abort(403, 'Unauthorized action.');
        }

        $journal = AccountingAccTransMapping::where('business_id', $business_id)
            ->where('type', 'journal_entry')
            ->where('id', $id)
            ->firstOrFail();
        $accounts_transactions = AccountingAccountsTransaction::with('account')
            ->where('acc_trans_mapping_id', $id)
            ->get()->toArray();
        $allCenters = CostCenter::query()->get();

        return view('accounting::journal_entry.edit')
            ->with(compact('journal', 'accounts_transactions', 'allCenters'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        try {
            $user_id = request()->session()->get('user.id');

            $account_ids = $request->get('account_id');
            $accounts_transactions_id = $request->get('accounts_transactions_id');
            $credits = $request->get('credit');
            $debits = $request->get('debit');
            $journal_date = $request->get('journal_date');
            $additional_notes = $request->get('additional_notes');
            $cost_centers = $request->get('cost_center');

            $journalDateParsed = $this->util->uf_date($journal_date, true);
            $this->periodLockService->assertUnlocked($business_id, $journalDateParsed);

            $parseAmount = fn ($v) => $this->util->num_uf($v ?? 0);
            $validation = JournalEntryValidator::validateJournalLines($account_ids ?? [], $debits ?? [], $credits ?? [], $parseAmount);
            if (! $validation['ok']) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => __('accounting::lang.journal_validation_'.$validation['error']),
                ]);
            }

            if (! JournalEntryValidator::accountsBelongToBusiness($account_ids ?? [], $business_id)) {
                return redirect()->back()->with('status', [
                    'success' => 0,
                    'msg' => __('accounting::lang.journal_invalid_accounts'),
                ]);
            }

            DB::beginTransaction();

            $acc_trans_mapping = AccountingAccTransMapping::where('business_id', $business_id)
                ->where('type', 'journal_entry')
                ->where('id', $id)
                ->firstOrFail();

            $accountingAccTransMappingHistory = AccountingAccTransMappingHistory::create([
                'accounting_accounts_transactions_history_id' => $id,
                "business_id" => $acc_trans_mapping->business_id,
                "ref_no" => $acc_trans_mapping->ref_no,
                "type" => $acc_trans_mapping->type,
                "created_by" => Auth::user()->id,
                "operation_date" => $acc_trans_mapping->operation_date,
                "note" => $acc_trans_mapping->note,
                'path_file' => $acc_trans_mapping->path_file,
            ]);
            if ($request->hasFile('attachment')) {
                $attachment = $request->file('attachment');
                $attachment_name = $attachment->store('/journal_entry');
                $acc_trans_mapping->path_file = $attachment_name;
            }
            $acc_trans_mapping->note = $request->get('note');
            $acc_trans_mapping->operation_date = $journalDateParsed;
            $acc_trans_mapping->update();

            //save details in account trnsactions table
            foreach ($account_ids as $index => $account_id) {
                if (!empty($account_id)) {
                    $transaction_row = [];
                    $transaction_row['accounting_account_id'] = $account_id;

                    if (!empty($credits[$index])) {
                        $transaction_row['amount'] = $this->util->num_uf($credits[$index]);
                        $transaction_row['type'] = 'credit';
                    }

                    if (!empty($debits[$index])) {
                        $transaction_row['amount'] = $this->util->num_uf($debits[$index]);
                        $transaction_row['type'] = 'debit';
                    }
                    $transaction_row['cost_center_id'] = $cost_centers[$index] ?? null;

                    $transaction_row['additional_notes'] = $additional_notes[$index] ?? '';
                  
                    $transaction_row['created_by'] = $user_id;
                    $transaction_row['operation_date'] = $journalDateParsed;
                    $transaction_row['sub_type'] = 'journal_entry';
                    $transaction_row['acc_trans_mapping_id'] = $acc_trans_mapping->id;

                    if (!empty($accounts_transactions_id[$index])) {
                        $accounts_transactions = AccountingAccountsTransaction::find($accounts_transactions_id[$index]);
                        AccountingAccountsTransactionHistory::create([
                            'acc_trans_mapping_history_id' => $accountingAccTransMappingHistory->id,
                            "accounting_account_id" => $accounts_transactions->accounting_account_id,
                            "acc_trans_mapping_id" => $accounts_transactions->acc_trans_mapping_id,
                            "transaction_id" => $accounts_transactions->transaction_id,
                            "transaction_payment_id" => $accounts_transactions->transaction_payment_id,
                            "amount" => $accounts_transactions->amount,
                            "type" => $accounts_transactions->type,
                            "sub_type" => $accounts_transactions->sub_type,
                            "map_type" => $accounts_transactions->map_type,
                            "created_by" => Auth::user()->id,
                            "operation_date" => $accounts_transactions->operation_date,
                            "note" => $accounts_transactions->note,
                            'additional_notes' => $accounts_transactions->additional_notes,
                            'cost_center_id' => $accounts_transactions->cost_center_id,


                        ]);
                        $accounts_transactions->fill($transaction_row);
                        $accounts_transactions->update();
                    } else {
                        $accounts_transactions = new AccountingAccountsTransaction();
                        $accounts_transactions->fill($transaction_row);
                        $accounts_transactions->save();
                    }
                } elseif (!empty($accounts_transactions_id[$index])) {
                    AccountingAccountsTransaction::where('id', $accounts_transactions_id[$index])->delete();
                }
            }

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.updated_success'),
            ];

            DB::commit();
        } catch (\RuntimeException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return redirect()->back()->with('status', [
                'success' => 0,
                'msg' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->route('journal-entry.index')->with('status', $output);
    }

    public function history_index($id)
    {
        $business_id = request()->session()->get('user.business_id');
       
        if (request()->ajax()) {

            $journal = AccountingAccTransMappingHistory::where('accounting_accounts_transactions_history_id', $id)
                ->where('accounting_acc_trans_mapping_histories.business_id', $business_id)
                ->join('users as u', 'accounting_acc_trans_mapping_histories.created_by', 'u.id')
                ->where('type', 'journal_entry')
                ->select([
                    'accounting_acc_trans_mapping_histories.id', 'ref_no', 'operation_date', 'note', 'accounting_acc_trans_mapping_histories.path_file', 'accounting_acc_trans_mapping_histories.created_at',
                    DB::raw("CONCAT(COALESCE(u.surname, ''),' ',COALESCE(u.first_name, ''),' ',COALESCE(u.last_name,'')) as added_by"),
                ]);

            return Datatables::of($journal)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                                <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                                    data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                                    </span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                       
                            $html .= '<li>
                                <a href="' . action('\Modules\Accounting\Http\Controllers\JournalEntryController@history_view', [$row->id]) . '"
                                 data-href="' . action('\Modules\Accounting\Http\Controllers\JournalEntryController@history_view', [$row->id]) . '">
                                        <i class="fas fa-history" aria-hidden="true"></i>' . __("accounting::lang.history_edit") . '
                                   </a>
                                </li>';
                       



                        $html .= '</ul></div>';

                        return $html;
                    }
                )
                ->addColumn('path_file', function ($row) {
                    $html = '';
                    if (!empty($row->path_file)) {
                        $html .= '<button class="btn btn-xs btn-info "  onclick="window.location.href = \'/uploads/' . $row->path_file . '\'"><i class="fa fa-eye"></i> ' . __('accounting::lang.attachment_view') . '</button>';
                        '&nbsp;';
                    } else {
                        $html .= '<span class="text-warning">' . __('accounting::lang.no_attachment_to_show') . '</span>';
                    }
                    return $html;
                })
                ->rawColumns(['action', 'path_file'])
                ->make(true);
        }

        return view('accounting::journal_entry.history_index');
    }

    public function history_view($id)
    {
        $business_id = request()->session()->get('user.business_id');
       

        $journal = AccountingAccTransMappingHistory::where('business_id', $business_id)
            ->where('type', 'journal_entry')
            ->where('id', $id)
            ->firstOrFail();
        $accounts_transactions = AccountingAccountsTransactionHistory::with('account')
            ->where('acc_trans_mapping_history_id', $id)
            ->get()->toArray();

       
        $allCenters = CostCenter::query()->get();

        return view('accounting::journal_entry.history_view')
            ->with(compact('journal', 'accounts_transactions', 'allCenters'));
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        if (
            !(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') ||
                $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') ||
                (auth()->user()->can('accounting.delete_journal')))
        ) {
            abort(403, 'Unauthorized action.');
        }

        $user_id = request()->session()->get('user.id');

        $acc_trans_mapping = AccountingAccTransMapping::where('id', $id)
            ->where('business_id', $business_id)->firstOrFail();

        try {
            $this->periodLockService->assertUnlocked($business_id, $acc_trans_mapping->operation_date);
        } catch (\RuntimeException $e) {
            return [
                'success' => 0,
                'msg' => $e->getMessage(),
            ];
        }

        if (!empty($acc_trans_mapping)) {
            $acc_trans_mapping->delete();
            AccountingAccountsTransaction::where('acc_trans_mapping_id', $id)->delete();
        }

        return [
            'success' => 1,
            'msg' => __('lang_v1.deleted_success'),
        ];
    }

    public function print($id)
    {
        $business_id = request()->session()->get('user.business_id');
      
        $journal = AccountingAccTransMapping::where('business_id', $business_id)
            ->where('type', 'journal_entry')
            ->where('id', $id)
            ->firstOrFail();

          $journal_history = AccountingAccTransMappingHistory::where('accounting_accounts_transactions_history_id', $id)
            ->latest('created_at')
            ->first();
        if ($journal_history) {
            $journal['latest_update'] = $journal_history->creator->first_name . ' ' . $journal_history->creator->last_name;
            $journal['latest_update_at'] = $journal_history->created_at;
        } else {
            $journal['latest_update'] = ' - ';
            $journal['latest_update_at'] =null;
        }
        $accounts_transactions = AccountingAccountsTransaction::with('account')
            ->where('acc_trans_mapping_id', $journal->id)
            ->get();

        return view('accounting::journal_entry.print', compact('journal', 'accounts_transactions'));
    }
}