<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignationComp extends Model
{
    protected $table = 'tbl_designation_comp';
    protected $guarded=[];
    protected $primaryKey = 'designation_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 