<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProStock extends Model
{
    protected $table = 'tbl_pro_stock';
    protected $guarded=[];
    protected $primaryKey = 'w_stock_old_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 