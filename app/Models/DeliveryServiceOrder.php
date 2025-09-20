<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryServiceOrder extends Model
{
    protected $table = 'tbl_delivery_service_order';
    protected $guarded=[];
    protected $primaryKey = 'DO_ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 