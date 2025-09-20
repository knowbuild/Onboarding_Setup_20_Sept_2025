<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoStockHistory extends Model
{
    protected $table = 'tbl_demo_stock_history';
    protected $guarded=[];
    protected $primaryKey = 'history_stock_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
