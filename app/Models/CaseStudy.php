<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseStudy extends Model
{
    protected $table = 'tbl_case_study';
    protected $guarded=[];
    protected $primaryKey = 'case_study_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 