<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentComp extends Model
{
    protected $table = 'tbl_department_comp';
    protected $guarded=[];
    protected $primaryKey = 'department_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  