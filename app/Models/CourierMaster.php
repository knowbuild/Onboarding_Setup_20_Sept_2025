<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierMaster extends Model
{
    protected $table = 'tbl_courier_master';
    protected $guarded=[];
    protected $primaryKey = 'courier_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
