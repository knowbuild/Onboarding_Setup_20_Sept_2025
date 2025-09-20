<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SegmentMaster extends Model
{
    protected $table = 'tbl_segment_master';
    protected $guarded=[];
    protected $primaryKey = 'segment_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  