<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoStock extends Model
{
    protected $table = 'tbl_demo_stock';
    protected $guarded=[];
    protected $primaryKey = 'stock_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 