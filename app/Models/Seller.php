<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $table = 'tbl_seller';
    protected $guarded=[];
    protected $primaryKey = 'seller_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 