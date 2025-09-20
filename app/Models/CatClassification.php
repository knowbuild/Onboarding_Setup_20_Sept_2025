<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatClassification extends Model
{
    protected $table = 'tbl_cat_classification';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
