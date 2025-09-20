<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceBasis extends Model
{
       protected $table = 'tbl_price_basis';
    protected $primaryKey = 'price_basis_id';
    protected $guarded = [];

    // Scope for active products
    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    }
}
 