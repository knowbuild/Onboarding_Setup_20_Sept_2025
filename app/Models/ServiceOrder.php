<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    protected $table = 'tbl_service_order';
    protected $guarded=[];
    protected $primaryKey = 'service_orders_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 