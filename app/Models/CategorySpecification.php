<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorySpecification extends Model
{
    protected $table = 'tbl_category_specification';
    protected $guarded=[];
    protected $primaryKey = 'cat_specification_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
