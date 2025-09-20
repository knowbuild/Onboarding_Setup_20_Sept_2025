<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialNo extends Model
{
    protected $table = 'tbl_serial_no';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 