<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderService extends Model
{
    protected $table = 'tbl_order_service';
    protected $guarded=[];
    protected $primaryKey = 'service_orders_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 