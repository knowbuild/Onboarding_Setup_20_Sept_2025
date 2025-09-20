<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderTempService extends Model
{
    protected $table = 'tbl_delivery_order_temp_service';
    protected $guarded=[];
    protected $primaryKey = 'DO_ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 