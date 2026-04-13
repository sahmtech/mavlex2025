<?php

namespace Modules\Accounting\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Accounting\Entities\AccountingPeriodLock;

class PeriodLockController extends Controller
{
    public function __construct(protected ModuleUtil $moduleUtil) {}

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePeriodLocks($business_id);

        $locks = AccountingPeriodLock::where('business_id', $business_id)
            ->orderByDesc('lock_year')
            ->orderByDesc('lock_month')
            ->paginate(50);

        return view('accounting::period_lock.index', compact('locks'));
    }

    public function store(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePeriodLocks($business_id);

        $request->validate([
            'lock_year' => 'required|integer|min:2000|max:2100',
            'lock_month' => 'required|integer|min:1|max:12',
        ]);

        AccountingPeriodLock::firstOrCreate(
            [
                'business_id' => $business_id,
                'lock_year' => (int) $request->lock_year,
                'lock_month' => (int) $request->lock_month,
            ],
            ['created_by' => auth()->id()]
        );

        return redirect()
            ->action([self::class, 'index'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.added_success')]);
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $this->authorizePeriodLocks($business_id);

        $lock = AccountingPeriodLock::where('business_id', $business_id)->where('id', $id)->firstOrFail();
        $lock->delete();

        return redirect()
            ->action([self::class, 'index'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.deleted_success')]);
    }

    private function authorizePeriodLocks($business_id): void
    {
        if (
            ! (auth()->user()->can('superadmin')
                || auth()->user()->can('Admin#'.$business_id)
                || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'accounting_module')
                    && auth()->user()->can('accounting.period_locks')))
        ) {
            abort(403, 'Unauthorized action.');
        }
    }
}
