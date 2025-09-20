<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurrencyPricelist extends Model
{
    protected $table = 'tbl_currency_pricelist';
    protected $guarded=[];
    protected $primaryKey = 'pricelist_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency', 'id');
    }
}
     