<?php

namespace Modules\ZatcaIntegrationKsa\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ZatcaDocument extends Model
{
    use HasFactory;
    
    protected $guarded = ['id'];
}
