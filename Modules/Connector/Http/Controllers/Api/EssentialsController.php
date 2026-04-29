<?php

namespace Modules\Connector\Http\Controllers\Api;

use App\Business;
use App\Transaction;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @group Essentials (mobile)
 * @authenticated
 *
 * APIs for essentials mobile dashboard & payroll.
 */
class EssentialsController extends ApiController
{
    /**
     * @var \App\Utils\ModuleUtil
     */
    protected $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Get Payroll Details (Essentials)
     *
     * Returns payrolls for the given year up to the given month (inclusive).
     *
     * @queryParam year int required Example: 2026
     * @queryParam month int required Example: 4
     */
    public function getPayrollDetails(Request $request)
    {
        if (! $this->moduleUtil->isModuleInstalled('Essentials')) {
            abort(403, 'Unauthorized action.');
        }

        $validator = Validator::make($request->all(), [
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);
        if ($validator->fails()) {
            return $this->setStatusCode(422)->respondWithError($validator->errors()->first());
        }

        $year = (int) $request->query('year');
        $month = (int) $request->query('month');

        $user = Auth::user();
        $business_id = (int) $user->business_id;

        if (! (auth()->user()->can('superadmin') || $this->moduleUtil->hasThePermissionInSubscription($business_id, 'essentials_module'))) {
            return $this->respondUnauthorized();
        }

        $query = Transaction::where('transactions.business_id', $business_id)
            ->where('transactions.type', 'payroll')
            ->whereYear('transactions.transaction_date', $year)
            ->whereMonth('transactions.transaction_date', '<=', $month)
            ->orderBy('transactions.transaction_date');

        if (! $user->can('essentials.view_all_payroll')) {
            $query->where('transactions.expense_for', $user->id);
        } elseif ($request->filled('user_id')) {
            $query->where('transactions.expense_for', (int) $request->query('user_id'));
        }

        $rows = $query->get([
            'transactions.total_before_tax',
            'transactions.final_total',
            'transactions.essentials_amount_per_unit_duration',
            'transactions.exchange_rate',
            'transactions.essentials_allowances',
            'transactions.essentials_deductions',
            'transactions.transaction_date',
        ]);

        $data = [];
        foreach ($rows as $row) {
            $dt = \Carbon\Carbon::parse($row->transaction_date);

            $allowances = ! empty($row->essentials_allowances) ? json_decode($row->essentials_allowances, true) : [];
            $deductions = ! empty($row->essentials_deductions) ? json_decode($row->essentials_deductions, true) : [];

            $data[] = [
                'payroll' => [
                    'total_before_tax' => $row->total_before_tax ?? 0,
                    'final_total' => (string) ($row->final_total ?? '0'),
                    'essentials_amount_per_unit_duration' => (string) ($row->essentials_amount_per_unit_duration ?? '0'),
                    'exchange_rate' => (string) ($row->exchange_rate ?? '1.000'),
                ],
                'year' => (string) $dt->format('Y'),
                'allowances' => [
                    'allowance_names' => $allowances['allowance_names'] ?? [],
                    'allowance_amounts' => $allowances['allowance_amounts'] ?? [],
                    'allowance_types' => $allowances['allowance_types'] ?? [],
                    'allowance_percents' => $allowances['allowance_percents'] ?? ($allowances['allowance_percent'] ?? []),
                ],
                'deductions' => [
                    'deduction_names' => $deductions['deduction_names'] ?? [],
                    'deduction_amounts' => $deductions['deduction_amounts'] ?? [],
                    'deduction_types' => $deductions['deduction_types'] ?? [],
                    'deduction_percents' => $deductions['deduction_percents'] ?? ($deductions['deduction_percent'] ?? []),
                ],
                'month_name' => $dt->format('F'),
            ];
        }

        return response()->json([
            'data' => $data,
        ]);
    }
}

