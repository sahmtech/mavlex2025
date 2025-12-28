<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OpeningBalance extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table ="accounting_opening_balances";
    public function transaction(){
        return $this->hasOne(AccountingAccountsTransaction::class, 'id','acc_transaction_id');
    }
}