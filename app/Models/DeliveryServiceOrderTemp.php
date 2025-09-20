<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryServiceOrderTemp extends Model
{
    protected $table = 'tbl_delivery_service_order_temp';
    protected $guarded=[];
    protected $primaryKey = 'DO_ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 