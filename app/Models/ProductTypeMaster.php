<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTypeMaster extends Model
{
    protected $table = 'tbl_product_type_master';
    protected $guarded=[];
    protected $primaryKey = 'product_type_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  