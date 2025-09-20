<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsSpecification extends Model
{
    protected $table = 'tbl_products_specification';
    protected $guarded=[];
    protected $primaryKey = 'pro_specification_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 