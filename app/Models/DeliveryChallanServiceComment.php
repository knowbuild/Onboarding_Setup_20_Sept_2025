<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryChallanServiceComment extends Model
{
    protected $table = 'tbl_delivery_challan_service_comment';
    protected $guarded=[];
    protected $primaryKey = 'delivery_challan_comment_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 