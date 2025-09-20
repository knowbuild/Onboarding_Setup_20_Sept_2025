<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorMaster extends Model
{
    protected $table = 'vendor_master';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 