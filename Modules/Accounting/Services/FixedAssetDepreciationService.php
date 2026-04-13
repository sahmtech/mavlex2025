<?php

namespace Modules\Accounting\Services;

use App\Utils\Util;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\AccountingAccountsTransaction;
use Modules\Accounting\Entities\AccountingAccTransMapping;
use Modules\Accounting\Entities\FixedAsset;
use Modules\Accounting\Entities\FixedAssetDepreciation;
use Modules\Accounting\Utils\AccountingUtil;

class FixedAssetDepreciationService
{
    public function __construct(
        protected Util $util,
        protected AccountingUtil $accountingUtil,
        protected AccountingPeriodLockService $periodLockService
    ) {}

    /**
     * Post straight-line depreciation for a month. Returns null if already posted or amount is zero.
     *
     * @throws \RuntimeException
     */
    public function postMonthlyDepreciation(FixedAsset $asset, int $year, int $month, int $userId): ?FixedAssetDepreciation
    {
        if ($asset->status !== 'active') {
            throw new \RuntimeException(__('accounting::lang.fixed_asset_inactive'));
        }

        $exists = FixedAssetDepreciation::where('fixed_asset_id', $asset->id)
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->exists();

        if ($exists) {
            return null;
        }

        $depreciable = max(0.0, (float) $asset->cost - (float) $asset->salvage_value);
        $months = max(1, (int) $asset->useful_life_months);
        $amount = round($depreciable / $months, 4);

        if ($amount <= 0) {
            return null;
        }

        $operationDate = Carbon::create($year, $month, 1)->endOfMonth();
        $this->periodLockService->assertUnlocked((int) $asset->business_id, $operationDate);

        $businessId = (int) $asset->business_id;
        $accountingSettings = $this->accountingUtil->getAccountingSettings($businessId);

        return DB::transaction(function () use ($asset, $amount, $year, $month, $userId, $operationDate, $businessId, $accountingSettings) {
            $ref_count = $this->util->setAndGetReferenceCount('journal_entry');
            $prefix = ! empty($accountingSettings['journal_entry_prefix']) ? $accountingSettings['journal_entry_prefix'] : '';
            $ref_no = $this->util->generateReferenceNumber('journal_entry', $ref_count, $businessId, $prefix);

            $mapping = new AccountingAccTransMapping;
            $mapping->business_id = $businessId;
            $mapping->ref_no = $ref_no;
            $mapping->note = __('accounting::lang.depreciation_note', [
                'name' => $asset->name,
                'y' => $year,
                'm' => str_pad((string) $month, 2, '0', STR_PAD_LEFT),
            ]);
            $mapping->type = 'journal_entry';
            $mapping->created_by = $userId;
            $mapping->operation_date = $operationDate->format('Y-m-d H:i:s');
            $mapping->save();

            $debit = [
                'accounting_account_id' => $asset->depreciation_expense_account_id,
                'amount' => $amount,
                'type' => 'debit',
                'sub_type' => 'fixed_asset_depreciation',
                'acc_trans_mapping_id' => $mapping->id,
                'created_by' => $userId,
                'operation_date' => $mapping->operation_date,
            ];

            $credit = [
                'accounting_account_id' => $asset->accumulated_depreciation_account_id,
                'amount' => $amount,
                'type' => 'credit',
                'sub_type' => 'fixed_asset_depreciation',
                'acc_trans_mapping_id' => $mapping->id,
                'created_by' => $userId,
                'operation_date' => $mapping->operation_date,
            ];

            AccountingAccountsTransaction::create($debit);
            AccountingAccountsTransaction::create($credit);

            return FixedAssetDepreciation::create([
                'fixed_asset_id' => $asset->id,
                'period_year' => $year,
                'period_month' => $month,
                'amount' => $amount,
                'acc_trans_mapping_id' => $mapping->id,
            ]);
        });
    }
}
