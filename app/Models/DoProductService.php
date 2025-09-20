<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoProductService extends Model
{
    protected $table = 'tbl_do_products_service';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
