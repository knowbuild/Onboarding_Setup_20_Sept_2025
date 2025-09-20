<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderConsignee extends Model
{
    protected $table = 'tbl_delivery_order_consignee';
    protected $guarded=[];
    protected $primaryKey = 'consignee_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 