<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseLinkingTes extends Model
{
    protected $table = 'tbl_case_linking_tes';
    protected $guarded=[];
    protected $primaryKey = 'case_linking_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 