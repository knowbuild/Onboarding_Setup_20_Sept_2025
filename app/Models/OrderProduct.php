<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    protected $table = 'tbl_order_product';
    protected $guarded=[];
    protected $primaryKey = 'order_pros_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
    
    public function product()
{
    return $this->belongsTo(ProductMain::class, 'pro_id', 'pro_id');
}

public function productEntry()
{
    return $this->belongsTo(ProductEntry::class, 'proidentry', 'pro_id_entry');
}

public function prowiseDiscount()
{
    return $this->hasOne(ProwiseDiscount::class, 'proid', 'pro_id')
                ->whereColumn('orderid', 'order_id');
}
public function order()
{
    return $this->belongsTo(Order::class, 'order_id', 'orders_id');
}

}
  