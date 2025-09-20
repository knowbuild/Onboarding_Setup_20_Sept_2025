<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreightMaster extends Model
{
    protected $table = 'tbl_freight_master';
    protected $guarded=[];
    protected $primaryKey = 'freight_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 