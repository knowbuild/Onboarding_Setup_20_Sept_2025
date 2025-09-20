<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskTypeMaster extends Model
{
    protected $table = 'tbl_tasktype_master';
    protected $guarded=[];
    protected $primaryKey = 'tasktype_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  