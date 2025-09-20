<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceMaster extends Model
{
    protected $table = 'tbl_service_master';
    protected $guarded=[];
    protected $primaryKey = 'service_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  