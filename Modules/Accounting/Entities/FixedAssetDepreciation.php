<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class FixedAssetDepreciation extends Model
{
    protected $guarded = ['id'];

    protected $table = 'accounting_fixed_asset_depreciations';

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function journalMapping()
    {
        return $this->belongsTo(AccountingAccTransMapping::class, 'acc_trans_mapping_id');
    }
}
