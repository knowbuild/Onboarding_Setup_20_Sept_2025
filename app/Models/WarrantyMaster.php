<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyMaster extends Model
{
    protected $table = 'tbl_warranty_master';
    protected $guarded=[];
    protected $primaryKey = 'warranty_id';

 
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
   