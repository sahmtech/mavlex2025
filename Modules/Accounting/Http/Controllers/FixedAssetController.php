<?php

namespace Modules\Accounting\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\AccountingAccount;
use Modules\Accounting\Entities\FixedAsset;
use Modules\Accounting\Services\FixedAssetDepreciationService;

class FixedAssetController extends Controller
{
    public function __construct(
        protected ModuleUtil $moduleUtil,
        protected FixedAssetDepreciationService $depreciationService
    ) {}

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeFixedAssets($business_id);

        $assets = FixedAsset::where('business_id', $business_id)->orderBy('name')->paginate(30);

        return view('accounting::fixed_asset.index', compact('assets'));
    }

    public function create()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeFixedAssets($business_id);

        $accounts = AccountingAccount::where('business_id', $business_id)
            ->where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('accounting::fixed_asset.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeFixedAssets($business_id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'acquisition_date' => 'required|date',
            'cost' => 'required|numeric',
            'salvage_value' => 'nullable|numeric',
            'useful_life_months' => 'required|integer|min:1',
            'asset_account_id' => 'required|exists:accounting_accounts,id',
            'accumulated_depreciation_account_id' => 'required|exists:accounting_accounts,id',
            'depreciation_expense_account_id' => 'required|exists:accounting_accounts,id',
        ]);

        $ids = [
            $data['asset_account_id'],
            $data['accumulated_depreciation_account_id'],
            $data['depreciation_expense_account_id'],
        ];
        $ok = AccountingAccount::where('business_id', $business_id)->whereIn('id', $ids)->count() === 3;
        if (! $ok) {
            return redirect()->back()->with('status', ['success' => 0, 'msg' => __('accounting::lang.journal_invalid_accounts')]);
        }

        FixedAsset::create(array_merge($data, [
            'business_id' => $business_id,
            'salvage_value' => $data['salvage_value'] ?? 0,
            'status' => 'active',
            'created_by' => auth()->id(),
        ]));

        return redirect()
            ->action([self::class, 'index'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.added_success')]);
    }

    public function depreciate(Request $request, $id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizeFixedAssets($business_id);

        $request->validate([
            'period_year' => 'required|integer|min:2000|max:2100',
            'period_month' => 'required|integer|min:1|max:12',
        ]);

        $asset = FixedAsset::where('business_id', $business_id)->findOrFail($id);

        try {
            $posted = $this->depreciationService->postMonthlyDepreciation(
                $asset,
                (int) $request->period_year,
                (int) $request->period_month,
                (int) auth()->id()
            );
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('status', ['success' => 0, 'msg' => $e->getMessage()]);
        }

        if ($posted === null) {
            return redirect()->back()->with('status', ['success' => 0, 'msg' => __('accounting::lang.depreciation_already_or_zero')]);
        }

        return redirect()->back()->with('status', ['success' => 1, 'msg' => __('lang_v1.added_success')]);
    }

    private function authorizeFixedAssets($business_id): void
    {
        if (
            ! (auth()->user()->can('superadmin')
                || auth()->user()->can('Admin#'.$business_id)
                || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')
                    && auth()->user()->can('accounting.fixed_assets')))
        ) {
            abort(403, 'Unauthorized action.');
        }
    }
}
