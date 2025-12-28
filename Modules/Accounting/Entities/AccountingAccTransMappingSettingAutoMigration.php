<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountingAccTransMappingSettingAutoMigration extends Model
{
    use HasFactory;

    protected $fillable = [
        'accounting_account_id','cost_center_id',
        'business_id', 'ref_no', 'sub_type', 'type', 'created_by', 'operation_date', 'amount','journal_entry_number', 'mapping_setting_id'
    ];
}