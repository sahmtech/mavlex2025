<?php

namespace Modules\Accounting\Entities;

use App\Business;
use App\BusinessLocation;
use App\User;
use Illuminate\Database\Eloquent\Model;

class AccountingAccTransMapping extends Model
{
    protected $fillable = [];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}