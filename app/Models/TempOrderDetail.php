<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TempOrderDetail extends Model
{
    protected $table = 'tbl_temp_order_details';
    protected $guarded=[];
    protected $primaryKey = 'temp_orderID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 