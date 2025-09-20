<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrderProduct extends Model
{
    protected $table = 'tbl_service_order_product';
    protected $guarded=[];
    protected $primaryKey = 'service_order_pros_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 