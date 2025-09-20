<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyProductMaster extends Model
{
    protected $table = 'tbl_key_product_maste';
    protected $guarded=[];
    protected $primaryKey = 'key_product_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 