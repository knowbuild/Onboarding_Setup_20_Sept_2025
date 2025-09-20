<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompEmp extends Model
{
    protected $table = 'tbl_comp_emp';
    protected $guarded=[];
    protected $primaryKey = 'comp_emp_id';
    
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
