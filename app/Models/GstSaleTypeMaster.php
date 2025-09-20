<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GstSaleTypeMaster extends Model
{
    protected $table = 'tbl_gst_sale_type_master';
    protected $guarded=[];
    protected $primaryKey = 'gst_sale_type_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
