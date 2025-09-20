<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusMaster extends Model
{
    protected $table = 'tbl_order_status_master';
    protected $guarded=[];
    protected $primaryKey = 'order_status_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 