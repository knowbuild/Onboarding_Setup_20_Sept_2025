<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTypeClassMaster extends Model
{
    protected $table = 'tbl_product_type_class_master';
    protected $guarded=[];
    protected $primaryKey = 'product_type_class_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
   