<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class AccountingAccountType extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function getAccountTypeNameAttribute()
    {
        $name = ! empty($this->business_id) ? $this->name : __('accounting::lang.'.$this->name);

        return $name;
    }

    public function getAccountTypeDescriptionAttribute()
    {
        if (empty($this->descriptiion)) {
            return '';
        }

        $descriptiion = ! empty($this->business_id) ?
        $this->descriptiion : __('accounting::lang.'.$this->descriptiion);

        return $descriptiion;
    }

    /**
     * Get the parent of the type
     */
    public function parent()
    {
        return $this->belongsTo('Modules\Accounting\Entities\AccountingAccountType', 'parent_id', 'id');
    }

    public static function accounting_primary_type()
    {
        $accounting_primary_type = [
            'asset' => ['label' => __('accounting::lang.asset'), 'GLC' => 1],
            'expenses' => ['label' => __('accounting::lang.expenses'), 'GLC' => 2],
            'income' => ['label' => __('accounting::lang.income'), 'GLC' => 3],
            'liability' => ['label' => __('accounting::lang.liability'), 'GLC' => 4],
            'cost_goods_sold' => ['label' => __('accounting::lang.cost_goods_sold'), 'GLC' => 5],
        ];

        return $accounting_primary_type;
    }
}