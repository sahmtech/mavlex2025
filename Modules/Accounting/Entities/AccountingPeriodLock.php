<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriodLock extends Model
{
    protected $guarded = ['id'];

    protected $table = 'accounting_period_locks';
}
