<?php

namespace Modules\Accounting\Services;

use Carbon\Carbon;
use Modules\Accounting\Entities\AccountingPeriodLock;

class AccountingPeriodLockService
{
    public function isLocked(int $businessId, $operationDate): bool
    {
        if ($operationDate === null) {
            return false;
        }

        $dt = $operationDate instanceof Carbon ? $operationDate->copy() : Carbon::parse($operationDate);

        return AccountingPeriodLock::where('business_id', $businessId)
            ->where('lock_year', (int) $dt->year)
            ->where('lock_month', (int) $dt->month)
            ->exists();
    }

    public function assertUnlocked(int $businessId, $operationDate): void
    {
        if ($this->isLocked($businessId, $operationDate)) {
            throw new \RuntimeException(__('accounting::lang.period_locked'));
        }
    }
}
