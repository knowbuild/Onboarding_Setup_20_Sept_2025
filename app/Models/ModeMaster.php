<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModeMaster extends Model
{
    protected $table = 'tbl_mode_master';
    protected $guarded=[];
    protected $primaryKey = 'mode_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
