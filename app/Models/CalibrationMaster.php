<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalibrationMaster extends Model
{
    protected $table = 'tbl_calibration_master';
    protected $guarded=[];
    protected $primaryKey = 'calibration_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
