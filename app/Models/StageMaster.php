<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StageMaster extends Model
{
    protected $table = 'tbl_stage_master';
    protected $guarded=[];
    protected $primaryKey = 'stage_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  