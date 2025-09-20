<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HvcMaster extends Model
{
    protected $table = 'tbl_hvc_master';
    protected $guarded=[];
    protected $primaryKey = 'hvc_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 