<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProductAttribute extends Model
{
    protected $table = 'tbl_order_product_attrbute';
    protected $guarded=[];
    protected $primaryKey = 'order_pro_attr_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 