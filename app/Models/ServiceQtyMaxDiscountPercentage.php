<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceQtyMaxDiscountPercentage extends Model
{
    protected $table = 'tbl_service_qty_max_discount_percentage';
    protected $guarded=[];
    protected $primaryKey = 'id_max_qty_percent';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 