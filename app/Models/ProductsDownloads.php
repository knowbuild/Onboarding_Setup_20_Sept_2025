<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsDownloads extends Model
{
    protected $table = 'tbl_products_downloads';
    protected $guarded=[];
    protected $primaryKey = 'id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 