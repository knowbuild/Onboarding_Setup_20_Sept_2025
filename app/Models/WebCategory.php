<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebCategory extends Model
{
    protected $table = 'tbl_web_category';
    protected $guarded=[];
    protected $primaryKey = 'web_cate_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
