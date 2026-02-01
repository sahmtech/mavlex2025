<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allergen extends Model
{
    use HasFactory;


    protected $fillable = ['name', 'icon', 'business_id'];

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'allergen_products'
        );
    }
}
