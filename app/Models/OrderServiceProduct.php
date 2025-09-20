<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderServiceProduct extends Model
{
    protected $table = 'tbl_order_service_product';
    protected $guarded=[];
    protected $primaryKey = 'service_order_pros_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 