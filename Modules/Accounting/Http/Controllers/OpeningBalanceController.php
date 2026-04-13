<?php

namespace Modules\Accounting\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccountType;
use Modules\Accounting\Entities\AccountingAccTransMapping;
use Modules\Accounting\Entities\CostCenter;
use Modules\Accounting\Entities\OpeningBalance;
use Modules\Accounting\Services\AccountingPeriodLockService;
use Yajra\DataTables\Facades\DataTables;
use App\Utils\ModuleUtil;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class OpeningBalanceController extends Controller
{
    protected $moduleUtil;

    protected AccountingPeriodLockService $periodLockService;

    public function __construct(ModuleUtil $moduleUtil, AccountingPeriodLockService $periodLockService)
    {
        $this->moduleUtil = $moduleUtil;
        $this->periodLockService = $periodLockService;
    }
    protected function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_superadmin = auth()->user()->can('superadmin') ? true : false;
        $is_admin = auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ? true : false;

        $can_opening_balances = auth()->user()->can('accounting.opening_balances');
        if (!($is_admin || $can_opening_balances || $is_superadmin)) {
            return redirect()->route('home')->with('status', [
                'success' => false,
                'msg' => __('message.unauthorized'),
            ]);
        }
        $can_OpeningBalance_delete = auth()->user()->can('accounting.OpeningBalance.delete');
        $sub_types_obj = AccountingAccount::query()->whereIn('account_primary_type', ['asset', 'liability'])
            ->where(function ($q) use ($business_id) {
                $q->whereNull('business_id')
                    ->orWhere('business_id', $business_id);
            })
            ->get();
        $sub_types = [];
        foreach ($sub_types_obj as $st) {
            $sub_types[] = [
                'id' => $st->id,
                'name' => $st->name,
                'status' => $st->status
            ];
        }
        if (request()->ajax()) {
            // $openingBalances = AccountingAccountsTransaction::query()->where('sub_type', 'opening_balance')
            //     ->orderBy('id');
            $openingBalances = AccountingAccountsTransaction::where('sub_type', 'opening_balance')
                ->whereHas('account', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->orderBy('id');
            return Datatables::of($openingBalances)
                ->addColumn(
                    'action',
                    function ($row) use ($is_admin, $can_OpeningBalance_delete, $is_superadmin) {
                        if ($is_admin  || $can_OpeningBalance_delete || $is_superadmin) {
                            $deleteUrl = action('\Modules\Accounting\Http\Controllers\OpeningBalanceController@destroy', [$row->id]);
                            return
                                '
                        <button data-href="' . $deleteUrl . '" class="btn btn-xs btn-danger delete_opening_balance_button"><i class="glyphicon glyphicon-trash"></i> ' . __("messages.delete") . '</button>
                    ';
                        }
                    }
                )
                ->addColumn('account_name', function ($row) {
                    $acc = $row->account->name ;
                    return $acc;
                })
                ->addColumn('account_number', function ($row) {
                    $acc = $row->account->gl_code;
                    return $acc;
                })
                ->addColumn('debit', function ($row) {
                    if ($row->type == 'debit') {
                        return $row->amount;
                    } else {
                        return 0;
                    }
                })
                ->addColumn('credit', function ($row) {
                    if ($row->type == 'credit') {
                        return $row->amount;
                    } else {
                        return 0;
                    }
                })
                ->rawColumns([
                    'action',
                    'account_name',
                    'account_number',
                    'debit',
                    'credit'
                ])
                ->make(true);
        }

        return view('accounting::opening_balance.index', compact('sub_types'));
    }

    protected function store(Request $request)
    {
        $rules = [
            // 'year' => 'required|String',
            'accounting_account_id' => 'required|String|exists:accounting_accounts,id',
            'type' => 'required|in:credit,debit',
            'value' => 'required|Numeric',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {

            $failedRules = $validator->failed();

            return redirect()->back()->with([
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ]);
        }
        try {
            $user_id = request()->session()->get('user.id');
            $validated = $validator->validated();
            $validated['created_by'] = auth()->user()->id;
            $validated['business_id'] = $request->session()->get('user.business_id');
            $operationDate = Carbon::now();
            $this->periodLockService->assertUnlocked((int) $validated['business_id'], $operationDate);

            DB::beginTransaction();
            $transaction = AccountingAccountsTransaction::query()->create([
                'accounting_account_id' => $validated['accounting_account_id'],
                'amount' => $validated['value'],
                'type' => $validated['type'] == 'credit' ? 'credit' : 'debit',
                'sub_type' => 'opening_balance',
                'operation_date' => $operationDate->format('Y-m-d H:i:s'),
                'created_by' => $user_id,
            ]);
            $validated['acc_transaction_id'] = $transaction->id;
            OpeningBalance::query()->create([
                'year' => date('Y-m-d'),
                'business_id' => $validated['business_id'],
                'type' => $validated['type'],
                'created_by' => $user_id,
                'acc_transaction_id' => $validated['acc_transaction_id']
            ]);
            DB::commit();
        } catch (\RuntimeException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            return redirect()->back()->with([
                'success' => false,
                'msg' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ]);
        }
        // return redirect()->back();
        return redirect()->back()->with([
            'success' => true,
            'msg' => __("lang_v1.added_success")
        ]);
        // return response()->json();
    }

    public function update(Request $request, $id)
    {
        $openingBalance = OpeningBalance::query()->find($id);
        $rules = [
            'year' => 'required|String',
            'accounting_account_id' => 'required|String|exists:accounting_accounts,id',
            'type' => 'required|in:credit,debit',
            'value' => 'required|Numeric',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {

            $failedRules = $validator->failed();
            //            if (isset($failedRules['ar_name']['min']) || isset($failedRules['ar_name']['max'])) {
            //                return response()->json(['fail' => __("messages.something_went_wrong")]);
            //            }
            return redirect()->back()->with([
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ]);
        }
        try {
            $openingBalance->update([
                'year' => $request->year,
                'value' => $request->value,
                'accounting_account_id' => $request->accounting_account_id,
                'type' => $request->type
            ]);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ]);
        }
        // return redirect()->back();
        return redirect()->back()->with([
            'success' => true,
            'msg' => __("lang_v1.updated_success")
        ]);
    }

 protected function destroy($id)
{
    if (request()->ajax()) {
        $transaction = AccountingAccountsTransaction::query()->find($id);
        if ($transaction) {
            $business_id = (int) request()->session()->get('user.business_id');
            try {
                $this->periodLockService->assertUnlocked($business_id, $transaction->operation_date);
            } catch (\RuntimeException $e) {
                return response()->json([
                    'success' => false,
                    'msg' => $e->getMessage(),
                ]);
            }

            $transaction->delete();

            $ob = OpeningBalance::query()->where('acc_transaction_id', $id)->first();
            if ($ob) {
                $ob->delete();
            }

            return response()->json([
                'success' => true,
                'msg' => __("lang_v1.deleted_success")
            ]);
        }

        return response()->json([
            'success' => false,
            'msg' => __("lang_v1.something_went_wrong")
        ]);
    }

    return redirect()->back()->with([
        'success' => true,
        'msg' => __("lang_v1.deleted_success")
    ]);
}

    protected function calcEquation()
    {
        
        $business_id = \request()->session()->get('user.business_id');
            
        $credit = AccountingAccountsTransaction::query()->where('sub_type', 'opening_balance')->where('type', 'credit')->whereHas('account', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->sum('amount');
        $debt = AccountingAccountsTransaction::query()->where('sub_type', 'opening_balance')->where('type', 'debit')->whereHas('account', function ($query) use ($business_id) {
                    $query->where('business_id', $business_id);
                })
                ->sum('amount');
        return response()->json(['credit' => $credit, 'debt' => $debt]);
    }

    public function viewImporte_openingBalance()
    {
        $is_admin = auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ? true : false;
        $can_import_opeining_balances = auth()->user()->can('accouning.import_opeining_balances');

        if (!($is_admin || $can_import_opeining_balances)) {
            return redirect()->route('home')->with('status', [
                'success' => false,
                'msg' => __('message.unauthorized'),
            ]);
        }
        return view('accounting::opening_balance.import_opening_balance');
    }
    public function importe_openingBalance(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $is_admin = auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ? true : false;
        $can_import_opeining_balances = auth()->user()->can('accouning.import_opeining_balances');

        if (!($is_admin || $can_import_opeining_balances)) {
            return redirect()->route('home')->with('status', [
                'success' => false,
                'msg' => __('message.unauthorized'),
            ]);
        }
        $openingBalanceBeforImport = OpeningBalance::count();
        try {
            if ($request->hasFile('opeining_balance_csv')) {
                $file = $request->file('opeining_balance_csv');
                $parsed_array = Excel::toArray([], $file);
                $opeining_balance_csv = array_splice($parsed_array[0], 1);
                DB::beginTransaction();
                foreach ($opeining_balance_csv as  $value) {
                    // dd($value[1]);
                    if (!$value[0] || !$value[1] || !$value[2]) {
                        continue;
                    } else {
                        $accountsAccount = AccountingAccount::where('name', $value[0])->orWhere('gl_code', $value[0])->first();
                        if (!$accountsAccount) {
                            continue;
                        } else {
                            $transaction = AccountingAccountsTransaction::create([
                                'accounting_account_id' => $accountsAccount->id, //accounting_account_id
                                'amount' => $value[2],  //value
                                'type' => $value[1] == 'credit' ? 'credit' : 'debit', //type
                                'sub_type' => 'opening_balance'
                            ]);

                            OpeningBalance::create([
                                'year' => date('Y-m-d'),
                                'business_id' => $business_id,
                                'type' => $value[1],
                                'acc_transaction_id' => $transaction->id,
                                'created_by' => Auth::user()->id
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            $openingBalanceAfterImport = OpeningBalance::count();


            if ($openingBalanceAfterImport > $openingBalanceBeforImport) {
                return redirect()->back()
                    ->with('status', [
                        'success' => 1,
                        'msg' => __('lang_v1.added_success')
                    ]);
            } else {
                return redirect()->back()
                    ->with('status', [
                        'success' => 0,
                        'msg' => __('messages.something_went_wrong'),
                    ]);
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('status', [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ]);
        }
    }
}