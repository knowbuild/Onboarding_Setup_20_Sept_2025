<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnqStatusMaster extends Model
{
    protected $table = 'tbl_enq_status_master';
    protected $guarded=[];
    protected $primaryKey = 'enq_status_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 