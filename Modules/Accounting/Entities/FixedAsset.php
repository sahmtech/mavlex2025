<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    protected $guarded = ['id'];

    protected $table = 'accounting_fixed_assets';

    protected $casts = [
        'acquisition_date' => 'date',
    ];

    public function depreciations()
    {
        return $this->hasMany(FixedAssetDepreciation::class, 'fixed_asset_id');
    }

    public function assetAccount()
    {
        return $this->belongsTo(AccountingAccount::class, 'asset_account_id');
    }

    public function accumulatedAccount()
    {
        return $this->belongsTo(AccountingAccount::class, 'accumulated_depreciation_account_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(AccountingAccount::class, 'depreciation_expense_account_id');
    }
}
