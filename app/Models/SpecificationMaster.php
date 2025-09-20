<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpecificationMaster extends Model
{
    protected $table = 'tbl_specification_master';
    protected $guarded=[];
    protected $primaryKey = 'specification_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 