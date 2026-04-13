<?php

namespace Modules\Accounting\Http\Controllers;

use App\Business;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccountType;
use Modules\Accounting\Entities\AccountingAccTransMapping;
use Modules\Accounting\Entities\AccountingBudget;
use Modules\Accounting\Utils\AccountingUtil;
use App\BusinessLocation;
use App\ExpenseCategory;
use Modules\Accounting\Entities\AccountingAccTransMappingSettingAutoMigration;
use Modules\Accounting\Entities\AccountingMappingSettingAutoMigration;

class SettingsController extends Controller
{
    protected $accountingUtil;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct(AccountingUtil $accountingUtil, ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->accountingUtil = $accountingUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $business_id = request()->session()->get('user.business_id');


        if (!(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') || auth()->user()->can('accounting.settings'))) {
            abort(403, 'Unauthorized action.');
        }

        $account_sub_types = AccountingAccountType::where('account_type', 'sub_type')
            ->where(function ($q) use ($business_id) {
                $q->whereNull('business_id')
                    ->orWhere('business_id', $business_id);
            })
            ->get();

        $account_types = AccountingAccountType::accounting_primary_type();

        $accounting_settings = $this->accountingUtil->getAccountingSettings($business_id);

        $business_locations = BusinessLocation::where('business_id', $business_id)->get();

        $expence_categories = ExpenseCategory::where('business_id', $business_id)->get();

        return view('accounting::settings.index')->with(compact('account_sub_types', 'account_types', 'accounting_settings', 'business_locations', 'expence_categories'));
    }

    public function resetData()
    {
            $business_id = request()->session()->get('user.business_id');


        if (!(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) || auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') || auth()->user()->can('accounting.rest_accounting_data'))) {
            abort(403, 'Unauthorized action.');
        }

        //reset logic
        AccountingBudget::join('accounting_accounts', 'accounting_budgets.accounting_account_id', '=', 'accounting_accounts.id')
            ->where('accounting_accounts.business_id', $business_id)
            ->delete();

        AccountingAccountType::where('business_id', $business_id)
            ->delete();

        AccountingAccTransMapping::where('business_id', $business_id)->delete();

        AccountingAccountsTransaction::join('accounting_accounts', 'accounting_accounts_transactions.accounting_account_id', '=', 'accounting_accounts.id')
            ->where('business_id', $business_id)->delete();

        AccountingAccount::where('business_id', $business_id)->delete();

        AccountingAccTransMappingSettingAutoMigration::where('business_id', $business_id)->delete();

        AccountingMappingSettingAutoMigration::where('business_id', $business_id)->delete();

        return redirect()->back();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        return view('accounting::create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function saveSettings(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('Admin#' . request()->session()->get('user.business_id')) ||  auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module') || auth()->user()->can('accounting.settings'))) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $prefixes = $request->only(['journal_entry_prefix', 'transfer_prefix']);

            Business::where('id', $business_id)
                ->update(['accounting_settings' => json_encode($prefixes)]);

            // Listeners (MapSellTransaction, MapPaymentTransaction, etc.) read from each location's column — must persist here.
            $accounting_default_map = $request->input('accounting_default_map', []);
            if (is_array($accounting_default_map)) {
                foreach ($accounting_default_map as $location_id => $details) {
                    BusinessLocation::where('id', $location_id)
                        ->where('business_id', $business_id)
                        ->update(['accounting_default_map' => json_encode($details)]);
                }
            }

            $output = [
                'success' => true,
                'msg' => __('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect()->back()->with(['status' => $output]);
    }

    /**
     * Show the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        return view('accounting::show');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        return view('accounting::edit');
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
        //
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
}