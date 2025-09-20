<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsCategories extends Model
{
    protected $table = 'tbl_products_categories';
    protected $guarded=[];
    protected $primaryKey = 'pro_cate_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
