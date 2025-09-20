<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'tbl_attendance';
    protected $guarded=[];
    protected $primaryKey = 'pid';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 