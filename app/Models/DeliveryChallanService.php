<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryChallanService extends Model
{
    protected $table = 'tbl_delivery_challan_service';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 