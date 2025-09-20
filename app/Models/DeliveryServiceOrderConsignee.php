<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryServiceOrderConsignee extends Model
{
    protected $table = 'tbl_delivery_service_order_consignee';
    protected $guarded=[];
    protected $primaryKey = 'consignee_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 