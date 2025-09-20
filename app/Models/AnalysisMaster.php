<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisMaster extends Model
{
    protected $table = 'tbl_analysis_master';
    protected $guarded=[];
    protected $primaryKey = 'analysis_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 