<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsImages extends Model
{
    protected $table = 'tbl_products_images';
    protected $guarded=[];
    protected $primaryKey = 'img_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 