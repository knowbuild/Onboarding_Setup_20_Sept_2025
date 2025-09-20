<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompNew extends Model
{
    protected $table = 'tbl_comp_new';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 