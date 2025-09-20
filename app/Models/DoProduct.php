<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoProduct extends Model
{
    protected $table = 'tbl_do_products';
    protected $guarded=[];

    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
    // In DoProduct.php
public function orderProduct()
{
    return $this->belongsTo(OrderProduct::class, 'OID', 'order_id');
}

}
  