<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class BankReconciliationItem extends Model
{
    protected $guarded = ['id'];

    protected $table = 'accounting_bank_reconciliation_items';

    protected $casts = [
        'is_cleared' => 'boolean',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function glLine()
    {
        return $this->belongsTo(AccountingAccountsTransaction::class, 'accounting_accounts_transaction_id');
    }
}
