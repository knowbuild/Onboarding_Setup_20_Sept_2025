<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductShopcart extends Model
{
    protected $table = 'tbl_product_shopcart';
    protected $guarded=[];

    protected $primaryKey = 'shop_pro_ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 