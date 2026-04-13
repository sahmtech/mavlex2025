<?php

namespace Modules\Accounting\Utils;

use Modules\Accounting\Entities\AccountingAccount;

class JournalEntryValidator
{
    /**
     * Validate parallel journal line arrays (same shape as JournalEntryController).
     *
     * @param  callable(string|float|int|null): float  $parseAmount
     * @return array{ok: bool, total_debit: float, total_credit: float, error: ?string}
     */
    public static function validateJournalLines(array $accountIds, array $debits, array $credits, callable $parseAmount): array
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accountIds as $index => $accountId) {
            $accountId = $accountId ?? '';
            $debitRaw = $debits[$index] ?? null;
            $creditRaw = $credits[$index] ?? null;

            $debitVal = $parseAmount($debitRaw);
            $creditVal = $parseAmount($creditRaw);

            if ($accountId === '' || $accountId === null) {
                if ($debitVal == 0.0 && $creditVal == 0.0) {
                    continue;
                }

                return ['ok' => false, 'total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'error' => 'missing_account'];
            }

            if ($debitVal > 0.0 && $creditVal > 0.0) {
                return ['ok' => false, 'total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'error' => 'both_sides'];
            }

            if ($debitVal == 0.0 && $creditVal == 0.0) {
                return ['ok' => false, 'total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'error' => 'zero_amount'];
            }

            if ($debitVal > 0.0) {
                $totalDebit += $debitVal;
            } else {
                $totalCredit += $creditVal;
            }
        }

        if (abs($totalDebit - $totalCredit) > 0.0001) {
            return ['ok' => false, 'total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'error' => 'unbalanced'];
        }

        if ($totalDebit == 0.0 && $totalCredit == 0.0) {
            return ['ok' => false, 'total_debit' => 0.0, 'total_credit' => 0.0, 'error' => 'empty'];
        }

        return ['ok' => true, 'total_debit' => $totalDebit, 'total_credit' => $totalCredit, 'error' => null];
    }

    /**
     * @param  array<int|string>  $accountIds
     */
    public static function accountsBelongToBusiness(array $accountIds, int $businessId): bool
    {
        $ids = array_values(array_unique(array_filter($accountIds, fn ($id) => $id !== '' && $id !== null)));
        if ($ids === []) {
            return false;
        }

        $count = AccountingAccount::where('business_id', $businessId)->whereIn('id', $ids)->count();

        return $count === count($ids);
    }
}
