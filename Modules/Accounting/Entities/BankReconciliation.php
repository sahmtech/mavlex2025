<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class BankReconciliation extends Model
{
    protected $guarded = ['id'];

    protected $table = 'accounting_bank_reconciliations';

    protected $casts = [
        'statement_date' => 'date',
    ];

    public function account()
    {
        return $this->belongsTo(AccountingAccount::class, 'accounting_account_id');
    }

    public function items()
    {
        return $this->hasMany(BankReconciliationItem::class, 'reconciliation_id');
    }
}
